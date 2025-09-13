<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

class BinaryWriter
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

    public function writeBytesWithLength(BinaryString $bytes, bool $use16BitLength = false): self
    {
        $length = $bytes->size();
        if ($use16BitLength) {
            if ($length > 65535) {
                throw new \InvalidArgumentException('String too long for 16-bit length field');
            }
            $this->writeUint16BE($length);
        } else {
            if ($length > 255) {
                throw new \InvalidArgumentException('String too long for 8-bit length field');
            }
            $this->writeByte($length);
        }

        $this->writeBytes($bytes);

        return $this;
    }

    public function writeUint16BE(int $value): self
    {
        if ($value < 0 || $value > 65535) {
            throw new \InvalidArgumentException('Uint16 value must be between 0 and 65535');
        }

        $this->buffer .= chr(($value >> 8) & 0xFF);
        $this->buffer .= chr($value & 0xFF);
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

    public function writeStringWithLength(BinaryString $string, bool $use16BitLength = false): self
    {
        if (!mb_check_encoding($string->value, 'UTF-8')) {
            throw new \InvalidArgumentException('String must be valid UTF-8');
        }

        $this->writeBytesWithLength($string, $use16BitLength);

        return $this;
    }
}
