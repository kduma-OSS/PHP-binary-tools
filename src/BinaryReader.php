<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

use Deprecated;
use RuntimeException;

final class BinaryReader
{
    private string $_data;

    public readonly int $length;

    public BinaryString $data {
        get {
            return BinaryString::fromString($this->_data);
        }
    }

    public int $position = 0 {
        get {
            return $this->position;
        }
        set {
            if ($value < 0 || $value > $this->length) {
                throw new RuntimeException(
                    sprintf('Invalid seek position: %d (valid range: 0-%d)', $value, $this->length)
                );
            }

            $this->position = $value;
        }
    }

    public int $remaining_bytes {
        get {
            return $this->length - $this->position;
        }
    }

    public bool $has_more_data {
        get {
            return $this->position < $this->length;
        }
    }

    public BinaryString $remaining_data {
        get {
            return BinaryString::fromString(substr($this->_data, $this->position));
        }
    }

    /**
     * Creates a new reader positioned at the start of the supplied buffer.
     *
     * @param BinaryString $data Buffer to read from.
     */
    public function __construct(BinaryString $data)
    {
        $this->_data = $data->toString();
        $this->length = $data->size();
    }

    /**
     * Reads the next byte from the stream.
     *
     * @throws RuntimeException When no more data is available.
     */
    public function readByte(): int
    {
        if ($this->position >= $this->length) {
            throw new RuntimeException('Unexpected end of data while reading byte');
        }

        return ord($this->_data[$this->position++]);
    }

    /**
     * Reads exactly $count bytes from the current position.
     *
     * @param int $count Number of bytes to read.
     * @throws RuntimeException When fewer than $count bytes remain.
     */
    public function readBytes(int $count): BinaryString
    {
        if ($this->position + $count > $this->length) {
            throw new RuntimeException('Unexpected end of data while reading ' . $count . ' bytes');
        }

        $result = substr($this->_data, $this->position, $count);
        $this->position += $count;

        return BinaryString::fromString($result);
    }

    /**
     * Reads variable-length data using exactly one of the supplied strategies (length, terminator, optional terminator, or padding).
     *
     * @param IntType|null $length Integer type that stores the byte length when using length mode.
     * @param Terminator|BinaryString|null $terminator Required terminator sequence when using terminator mode.
     * @param Terminator|BinaryString|null $optional_terminator Terminator sequence that may be absent (fully consumes buffer when missing).
     * @param Terminator|BinaryString|null $padding Single-byte padding value used for fixed-width fields.
     * @param int|null $padding_size Total field width in bytes when padding is enabled.
     * @throws \InvalidArgumentException When mutually exclusive modes are combined or configuration is invalid.
     * @throws RuntimeException When the data violates the expectations of the chosen mode.
     */
    public function readBytesWith(
        ?IntType $length = null,
        Terminator|BinaryString|null $terminator = null,
        Terminator|BinaryString|null $optional_terminator = null,
        Terminator|BinaryString|null $padding = null,
        ?int $padding_size = null,
    ): BinaryString {
        if ($padding === null && $padding_size !== null) {
            $padding = Terminator::NUL;
        }

        $modes = array_filter([
            'length' => $length,
            'terminator' => $terminator,
            'optional_terminator' => $optional_terminator,
            'padding' => $padding,
        ], static fn ($value) => $value !== null);

        if (count($modes) !== 1) {
            throw new \InvalidArgumentException('Exactly one of length, terminator, optional_terminator, or padding must be provided');
        }

        $selectedMode = array_key_first($modes);

        if ($selectedMode === 'length') {
            return $this->_readWithLength($length);
        }

        if ($selectedMode === 'padding') {
            return $this->_readWithPadding($padding, $padding_size);
        }

        return $this->_readWithTerminator($modes[$selectedMode], $selectedMode === 'optional_terminator');
    }

    /**
     * Reads an integer using the provided {@see IntType} definition.
     *
     * @param IntType $type Integer description covering width, signedness, and byte order.
     * @throws RuntimeException When the type is unsupported or the value cannot be represented.
     */
    public function readInt(IntType $type): int
    {
        if (!$type->isSupported()) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                sprintf('Cannot read %d-byte integers on %d-byte platform', $type->bytes(), PHP_INT_SIZE)
            );
            // @codeCoverageIgnoreEnd
        }

        $bytesCount = $type->bytes();
        $bytes = $this->readBytes($bytesCount)->value;

        if ($type->isLittleEndian()) {
            $bytes = strrev($bytes);
        }

        $value = 0;
        for ($i = 0; $i < $bytesCount; $i++) {
            $value = ($value << 8) | ord($bytes[$i]);
        }

        if ($type->isSigned()) {
            $bits = $bytesCount * 8;
            if ($bits <= PHP_INT_SIZE * 8) {
                $signBit = 1 << ($bits - 1);
                if (($value & $signBit) !== 0) {
                    $value -= 1 << $bits;
                }
            }
        }

        // Validate unsigned integers that may have wrapped due to PHP integer limits
        if (!$type->isSigned() && !$type->isValid($value)) {
            throw new RuntimeException(
                sprintf('Value exceeds maximum for %s on this platform', $type->name)
            );
        }

        return $value;
    }


    /**
     * Reads a fixed-length UTF-8 string.
     *
     * @param int $length Number of bytes to consume.
     * @throws RuntimeException When insufficient data remains or decoding fails.
     */
    public function readString(int $length): BinaryString
    {
        $bytes = $this->readBytes($length);

        if (!mb_check_encoding($bytes->value, 'UTF-8')) {
            $this->position -= $length;
            throw new RuntimeException('Invalid UTF-8 string');
        }


        return $bytes;
    }

    /**
     * Reads a UTF-8 string using one of the variable-length strategies (length, terminator, optional terminator, or padding).
     *
     * @param IntType|null $length Integer type specifying the length field when using length mode.
     * @param Terminator|BinaryString|null $terminator Required terminator.
     * @param Terminator|BinaryString|null $optional_terminator Optional terminator.
     * @param Terminator|BinaryString|null $padding Single-byte padding value for fixed-width fields.
     * @param int|null $padding_size Total field width when padding is enabled.
     * @throws \InvalidArgumentException When configuration is invalid.
     * @throws RuntimeException When decoding fails or the data violates mode rules.
     */
    public function readStringWith(
        ?IntType $length = null,
        Terminator|BinaryString|null $terminator = null,
        Terminator|BinaryString|null $optional_terminator = null,
        Terminator|BinaryString|null $padding = null,
        ?int $padding_size = null,
    ): BinaryString {
        $startPosition = $this->position;
        $string = $this->readBytesWith($length, $terminator, $optional_terminator, $padding, $padding_size);

        if (!mb_check_encoding($string->value, 'UTF-8')) {
            $this->position = $startPosition;
            throw new RuntimeException('Invalid UTF-8 string');
        }

        return $string;
    }

    /**
     * Returns the next byte without advancing the read pointer.
     *
     * @return int Unsigned byte value.
     * @throws RuntimeException When no more data remains.
     */
    public function peekByte(): int
    {
        if ($this->position >= $this->length) {
            throw new RuntimeException('Unexpected end of data while peeking byte');
        }

        return ord($this->_data[$this->position]);
    }

    /**
     * Returns the next $count bytes without advancing the read pointer.
     *
     * @param int $count Number of bytes to inspect.
     * @throws RuntimeException When fewer than $count bytes remain.
     */
    public function peekBytes(int $count): BinaryString
    {
        if ($this->position + $count > $this->length) {
            throw new RuntimeException('Unexpected end of data while peeking ' . $count . ' bytes');
        }

        return BinaryString::fromString(substr($this->_data, $this->position, $count));
    }

    /**
     * Advances the read pointer by $count bytes.
     *
     * @param int $count Number of bytes to skip.
     * @throws RuntimeException When insufficient data remains.
     */
    public function skip(int $count): void
    {
        if ($this->position + $count > $this->length) {
            throw new RuntimeException('Cannot skip ' . $count . ' bytes, not enough data');
        }

        $this->position += $count;
    }

    /**
     * Moves the read pointer to an absolute offset inside the buffer.
     *
     * @param int $position Zero-based offset to seek to.
     * @throws RuntimeException When the target lies outside the buffer.
     */
    public function seek(int $position): void
    {
        $this->position = $position;
    }

    // Deprecated methods

    #[Deprecated('Use readInt(IntType::UINT16) instead')]
    /**
     * @deprecated Use {@see readInt()} with {@see IntType::UINT16} instead.
     */
    public function readUint16BE(): int
    {
        return $this->readInt(IntType::UINT16);
    }

    #[Deprecated('Use readBytesWith(length: IntType::UINT8) or readBytesWith(length: IntType::UINT16) instead')]
    /**
     * @deprecated Use {@see readBytesWith()} with an explicit length type instead.
     *
     * @param bool $use16BitLength When true, reads a 16-bit length; otherwise an 8-bit length.
     */
    public function readBytesWithLength(bool $use16BitLength = false): BinaryString
    {
        return $this->readBytesWith($use16BitLength ? IntType::UINT16 : IntType::UINT8, null, null, null, null);
    }

    #[Deprecated('Use readStringWith(length: IntType::UINT8) or readStringWith(length: IntType::UINT16) instead')]
    /**
     * @deprecated Use {@see readStringWith()} with an explicit length type instead.
     *
     * @param bool $use16BitLength When true, reads a 16-bit length; otherwise an 8-bit length.
     */
    public function readStringWithLength(bool $use16BitLength = false): BinaryString
    {
        return $this->readStringWith($use16BitLength ? IntType::UINT16 : IntType::UINT8, null, null, null, null);
    }

    // Private methods

    private function _readWithLength(IntType $length): BinaryString
    {
        $dataLength = $this->readInt($length);

        if ($dataLength < 0) {
            throw new RuntimeException(sprintf('Negative length %d is invalid for %s', $dataLength, $length->name));
        }

        try {
            return $this->readBytes($dataLength);
        } catch (\RuntimeException $exception) {
            $this->position -= $length->bytes();
            throw $exception;
        }
    }

    private function _readWithTerminator(Terminator|BinaryString $terminator, bool $terminatorIsOptional): BinaryString
    {
        $terminatorBytes = $terminator instanceof Terminator ? $terminator->toBytes() : $terminator;
        $terminatorSize = $terminatorBytes->size();

        if ($terminatorSize === 0) {
            throw new \InvalidArgumentException('Terminator cannot be empty');
        }

        $remainingData = substr($this->_data, $this->position);
        $terminatorPosition = strpos($remainingData, $terminatorBytes->value);

        if ($terminatorPosition === false) {
            if ($terminatorIsOptional) {
                $this->position = $this->length;
                return BinaryString::fromString($remainingData);
            }

            throw new RuntimeException('Terminator not found before end of data');
        }

        $result = substr($remainingData, 0, $terminatorPosition);
        $this->position += $terminatorPosition + $terminatorSize;

        return BinaryString::fromString($result);
    }

    private function _readWithPadding(BinaryString|Terminator $padding, ?int $paddingSize): BinaryString
    {
        if ($paddingSize === null) {
            throw new \InvalidArgumentException('Padding size must be provided when padding is used');
        }

        $padding = $padding instanceof Terminator ? $padding->toBytes() : $padding;

        if ($paddingSize < 0) {
            throw new RuntimeException('Padding size cannot be negative');
        }

        if ($padding->size() !== 1) {
            throw new \InvalidArgumentException('Padding must be exactly one byte');
        }

        if ($paddingSize === 0) {
            return BinaryString::fromString('');
        }

        $raw = $this->readBytes($paddingSize);
        $dataString = $raw->value;
        $padByte = $padding->value;
        $padPosition = strpos($dataString, $padByte);

        if ($padPosition === false) {
            return $raw;
        }

        for ($i = $padPosition; $i < $paddingSize; $i++) {
            if ($dataString[$i] !== $padByte) {
                throw new RuntimeException('Invalid padding sequence encountered');
            }
        }

        $data = substr($dataString, 0, $padPosition);

        return BinaryString::fromString($data);
    }
}
