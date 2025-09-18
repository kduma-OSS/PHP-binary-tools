<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

use Deprecated;

final class BinaryWriter
{
    private string $buffer = '';

    public function getBuffer(): BinaryString
    {
        return BinaryString::fromString($this->buffer);
    }

    public function getLength(): int
    {
        return strlen($this->buffer);
    }

    public function reset(): void
    {
        $this->buffer = '';
    }

    public function writeByte(int $byte): self
    {
        if ($byte < 0 || $byte > 255) {
            throw new \InvalidArgumentException('Byte value must be between 0 and 255');
        }

        $this->buffer .= chr($byte);
        return $this;
    }

    public function writeBytes(BinaryString $bytes): self
    {
        $this->buffer .= $bytes->value;

        return $this;
    }

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


    public function writeString(BinaryString $string): self
    {
        if (!mb_check_encoding($string->value, 'UTF-8')) {
            throw new \InvalidArgumentException('String must be valid UTF-8');
        }

        $this->writeBytes($string);

        return $this;
    }

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
    public function writeUint16BE(int $value): self
    {
        return $this->writeInt(IntType::UINT16, $value);
    }

    #[Deprecated('Use writeBytesWith($bytes, length: IntType::UINT8) or writeBytesWith($bytes, length: IntType::UINT16) instead')]
    public function writeBytesWithLength(BinaryString $bytes, bool $use16BitLength = false): self
    {
        return $this->writeBytesWith($bytes, $use16BitLength ? IntType::UINT16 : IntType::UINT8, null, null, null, null);
    }

    #[Deprecated('Use writeStringWith($string, length: IntType::UINT8) or writeStringWith($string, length: IntType::UINT16) instead')]
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
