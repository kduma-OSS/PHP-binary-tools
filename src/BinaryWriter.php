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

    public function writeBytesWith(BinaryString $bytes, ?IntType $length = null, Terminator|BinaryString|null $terminator = null): self
    {
        if (($length === null && $terminator === null) || ($length !== null && $terminator !== null)) {
            throw new \InvalidArgumentException('Exactly one of length or terminator must be provided');
        }

        if ($length !== null) {
            return $this->_writeWithLength($bytes, $length);
        } else {
            return $this->_writeWithTerminator($bytes, $terminator);
        }
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

    public function writeStringWith(BinaryString $string, ?IntType $length = null, Terminator|BinaryString|null $terminator = null): self
    {
        if (!mb_check_encoding($string->value, 'UTF-8')) {
            throw new \InvalidArgumentException('String must be valid UTF-8');
        }

        return $this->writeBytesWith($string, $length, $terminator);
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
        return $this->writeBytesWith($bytes, $use16BitLength ? IntType::UINT16 : IntType::UINT8, null);
    }

    #[Deprecated('Use writeStringWith($string, length: IntType::UINT8) or writeStringWith($string, length: IntType::UINT16) instead')]
    public function writeStringWithLength(BinaryString $string, bool $use16BitLength = false): self
    {
        return $this->writeStringWith($string, $use16BitLength ? IntType::UINT16 : IntType::UINT8, null);
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

        if ($data->contains($terminatorBytes)) {
            throw new \InvalidArgumentException('Data contains terminator sequence');
        }

        $this->writeBytes($data);
        $this->writeBytes($terminatorBytes);

        return $this;
    }
}
