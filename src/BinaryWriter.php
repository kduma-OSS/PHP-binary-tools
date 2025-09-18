<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

use Deprecated;

final class BinaryWriter
{
    private string $buffer = '';

    /**
     * Returns the buffered bytes as a BinaryString without resetting the writer.
     */
    public function getBuffer(): BinaryString
    {
        return BinaryString::fromString($this->buffer);
    }

    /**
     * Returns the number of bytes written so far.
     */
    public function getLength(): int
    {
        return strlen($this->buffer);
    }

    /**
     * Clears the buffer so subsequent writes start from an empty state.
     */
    public function reset(): void
    {
        $this->buffer = '';
    }

    /**
     * Appends a single byte value (0-255) to the buffer.
     *
     * @param int $byte Byte value to write.
     * @throws \InvalidArgumentException When the value is outside the valid byte range.
     */
    public function writeByte(int $byte): self
    {
        if ($byte < 0 || $byte > 255) {
            throw new \InvalidArgumentException('Byte value must be between 0 and 255');
        }

        $this->buffer .= chr($byte);
        return $this;
    }

    /**
     * Appends raw bytes to the buffer.
     *
     * @param BinaryString $bytes Data to append.
     */
    public function writeBytes(BinaryString $bytes): self
    {
        $this->buffer .= $bytes->value;

        return $this;
    }

    /**
     * Writes variable-length data using one of the available strategies: typed length, terminator or fixed padding.
     *
     * @note When {@code optional_terminator} is supplied it currently behaves the same as {@code terminator} but emits a notice.
     *
     * @param BinaryString $bytes Data to write.
     * @param IntType|null $length Integer type describing the length field when using length mode.
     * @param Terminator|BinaryString|null $terminator Mandatory terminator sequence.
     * @param Terminator|BinaryString|null $optional_terminator Optional terminator sequence (currently emits a notice and behaves like $terminator).
     * @param Terminator|BinaryString|null $padding Single-byte padding value for fixed-width fields.
     * @param int|null $padding_size Total field width when padding is enabled.
     * @throws \InvalidArgumentException When configuration is invalid or the data violates the chosen mode.
     */
    public function writeBytesWith(
        BinaryString $bytes,
        ?IntType $length = null,
        Terminator|BinaryString|null $terminator = null,
        Terminator|BinaryString|null $optional_terminator = null,
        Terminator|BinaryString|null $padding = null,
        ?int $padding_size = null,
    ): self
    {
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

        $modeKey = array_key_first($modes);

        if ($modeKey === 'length') {
            return $this->_writeWithLength($bytes, $length);
        }

        if ($modeKey === 'padding') {
            return $this->_writeWithPadding($bytes, $padding, $padding_size);
        }

        if ($modeKey === 'optional_terminator') {
            trigger_error(
                'UNSTABLE API: optional_terminator has no effect when writing IN THIS VERSION; data will always be terminated IN THIS VERSION; it will probably change in future',
                E_USER_NOTICE
            );
        }

        return $this->_writeWithTerminator($bytes, $modes[$modeKey]);
    }

    /**
     * Serialises an integer according to the provided {@see IntType} definition.
     *
     * @param IntType $type Integer description covering width, signedness, and byte order.
     * @param int $value Value to serialise.
     * @throws \RuntimeException When the type is unsupported on this platform.
     * @throws \InvalidArgumentException When the value lies outside the type's range.
     */
    public function writeInt(IntType $type, int $value): self
    {
        if (!$type->isSupported()) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(
                sprintf('Cannot write %d-byte integers on %d-byte platform', $type->bytes(), PHP_INT_SIZE)
            );
            // @codeCoverageIgnoreEnd
        }

        $bytesCount = $type->bytes();

        if (!$type->isValid($value)) {
            throw new \InvalidArgumentException(
                sprintf('Value %d is out of range for %s', $value, $type->name)
            );
        }

        // Handle negative values for signed types
        if ($type->isSigned() && $value < 0) {
            $value = (1 << ($bytesCount * 8)) + $value;
        }

        $bytes = '';
        for ($i = $bytesCount - 1; $i >= 0; $i--) {
            $bytes .= chr(($value >> ($i * 8)) & 0xFF);
        }

        if ($type->isLittleEndian()) {
            $bytes = strrev($bytes);
        }

        $this->buffer .= $bytes;
        return $this;
    }


    /**
     * Writes a UTF-8 validated string without terminator or padding.
     *
     * @param BinaryString $string UTF-8 string data to emit.
     * @throws \InvalidArgumentException When the data is not valid UTF-8.
     */
    public function writeString(BinaryString $string): self
    {
        if (!mb_check_encoding($string->value, 'UTF-8')) {
            throw new \InvalidArgumentException('String must be valid UTF-8');
        }

        $this->writeBytes($string);

        return $this;
    }

    /**
     * Writes a UTF-8 string using one of the variable-length strategies.
     *
     * @param BinaryString $string UTF-8 string data to emit.
     * @param IntType|null $length Integer type describing the length field when using length mode.
     * @param Terminator|BinaryString|null $terminator Mandatory terminator sequence.
     * @param Terminator|BinaryString|null $optional_terminator Optional terminator sequence (currently emits a notice and behaves like $terminator).
     * @param Terminator|BinaryString|null $padding Single-byte padding value for fixed-width fields.
     * @param int|null $padding_size Total field width when padding is enabled.
     * @throws \InvalidArgumentException When configuration is invalid or the string is not UTF-8.
     */
    public function writeStringWith(
        BinaryString $string,
        ?IntType $length = null,
        Terminator|BinaryString|null $terminator = null,
        Terminator|BinaryString|null $optional_terminator = null,
        Terminator|BinaryString|null $padding = null,
        ?int $padding_size = null,
    ): self
    {
        if (!mb_check_encoding($string->value, 'UTF-8')) {
            throw new \InvalidArgumentException('String must be valid UTF-8');
        }

        return $this->writeBytesWith($string, $length, $terminator, $optional_terminator, $padding, $padding_size);
    }

    // Deprecated methods

    #[Deprecated('Use writeInt(IntType::UINT16, $value) instead')]
    /**
     * @deprecated Use {@see writeInt()} with {@see IntType::UINT16} instead.
     *
     * @param int $value Unsigned 16-bit value.
     */
    public function writeUint16BE(int $value): self
    {
        return $this->writeInt(IntType::UINT16, $value);
    }

    #[Deprecated('Use writeBytesWith($bytes, length: IntType::UINT8) or writeBytesWith($bytes, length: IntType::UINT16) instead')]
    /**
     * @deprecated Use {@see writeBytesWith()} with an explicit length type instead.
     *
     * @param BinaryString $bytes Payload to write.
     * @param bool $use16BitLength When true, emits a 16-bit length; otherwise an 8-bit length.
     */
    public function writeBytesWithLength(BinaryString $bytes, bool $use16BitLength = false): self
    {
        return $this->writeBytesWith($bytes, $use16BitLength ? IntType::UINT16 : IntType::UINT8, null, null, null, null);
    }

    #[Deprecated('Use writeStringWith($string, length: IntType::UINT8) or writeStringWith($string, length: IntType::UINT16) instead')]
    /**
     * @deprecated Use {@see writeStringWith()} with an explicit length type instead.
     *
     * @param BinaryString $string UTF-8 string to write.
     * @param bool $use16BitLength When true, emits a 16-bit length; otherwise an 8-bit length.
     */
    public function writeStringWithLength(BinaryString $string, bool $use16BitLength = false): self
    {
        return $this->writeStringWith($string, $use16BitLength ? IntType::UINT16 : IntType::UINT8, null, null, null, null);
    }

    // Private methods

    private function _writeWithLength(BinaryString $data, IntType $length): self
    {
        $dataLength = $data->size();
        $maxLength = $length->maxValue();

        if ($dataLength > $maxLength) {
            throw new \InvalidArgumentException("Data too long for {$length->name} length field (max: {$maxLength})");
        }

        $this->writeInt($length, $dataLength);
        $this->writeBytes($data);

        return $this;
    }

    private function _writeWithTerminator(BinaryString $data, Terminator|BinaryString $terminator): self
    {
        $terminatorBytes = $terminator instanceof Terminator ? $terminator->toBytes() : $terminator;

        if ($terminatorBytes->size() === 0) {
            throw new \InvalidArgumentException('Terminator cannot be empty');
        }

        if ($data->contains($terminatorBytes)) {
            throw new \InvalidArgumentException('Data contains terminator sequence');
        }

        $this->writeBytes($data);
        $this->writeBytes($terminatorBytes);

        return $this;
    }

    private function _writeWithPadding(BinaryString $data, BinaryString|Terminator $padding, ?int $paddingSize): self
    {
        $padding = $padding instanceof Terminator ? $padding->toBytes() : $padding;

        if ($paddingSize === null) {
            throw new \InvalidArgumentException('Padding size must be provided when padding is used');
        }

        if ($paddingSize < 0) {
            throw new \InvalidArgumentException('Padding size cannot be negative');
        }

        if ($padding->size() !== 1) {
            throw new \InvalidArgumentException('Padding must be exactly one byte');
        }

        if ($data->contains($padding)) {
            throw new \InvalidArgumentException('Data contains padding byte');
        }

        $dataLength = $data->size();

        if ($dataLength > $paddingSize) {
            throw new \InvalidArgumentException('Data too long for padding size');
        }

        $this->writeBytes($data);

        if ($dataLength < $paddingSize) {
            $padByte = $padding->value;
            $this->buffer .= str_repeat($padByte, $paddingSize - $dataLength);
        }

        return $this;
    }
}
