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
                throw new RuntimeException('Invalid seek position: ' . $value);
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

    public function __construct(BinaryString $data)
    {
        $this->_data = $data->toString();
        $this->length = $data->size();
    }

    public function readByte(): int
    {
        if ($this->position >= $this->length) {
            throw new RuntimeException('Unexpected end of data while reading byte');
        }

        return ord($this->_data[$this->position++]);
    }

    public function readBytes(int $count): BinaryString
    {
        if ($this->position + $count > $this->length) {
            throw new RuntimeException('Unexpected end of data while reading ' . $count . ' bytes');
        }

        $result = substr($this->_data, $this->position, $count);
        $this->position += $count;

        return BinaryString::fromString($result);
    }

    public function readBytesWith(IntType $length): BinaryString
    {
        $dataLength = $this->readInt($length);

        try {
            return $this->readBytes($dataLength);
        } catch (\RuntimeException $exception) {
            $this->position -= $length->bytes();
            throw $exception;
        }
    }

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


    public function readString(int $length): BinaryString
    {
        $bytes = $this->readBytes($length);

        if (!mb_check_encoding($bytes->value, 'UTF-8')) {
            $this->position -= $length;
            throw new RuntimeException('Invalid UTF-8 string');
        }


        return $bytes;
    }

    public function readStringWith(IntType $length): BinaryString
    {
        $string = $this->readBytesWith($length);

        if (!mb_check_encoding($string->value, 'UTF-8')) {
            $this->position -= $length->bytes() + $string->size();
            throw new RuntimeException('Invalid UTF-8 string');
        }

        return $string;
    }

    public function peekByte(): int
    {
        if ($this->position >= $this->length) {
            throw new RuntimeException('Unexpected end of data while peeking byte');
        }

        return ord($this->_data[$this->position]);
    }

    public function peekBytes(int $count): BinaryString
    {
        if ($this->position + $count > $this->length) {
            throw new RuntimeException('Unexpected end of data while peeking ' . $count . ' bytes');
        }

        return BinaryString::fromString(substr($this->_data, $this->position, $count));
    }

    public function skip(int $count): void
    {
        if ($this->position + $count > $this->length) {
            throw new RuntimeException('Cannot skip ' . $count . ' bytes, not enough data');
        }

        $this->position += $count;
    }

    public function seek(int $position): void
    {
        $this->position = $position;
    }

    // Deprecated methods

    #[Deprecated('Use readInt(IntType::UINT16) instead')]
    public function readUint16BE(): int
    {
        return $this->readInt(IntType::UINT16);
    }

    #[Deprecated('Use readBytesWith(length: IntType::UINT8) or readBytesWith(length: IntType::UINT16) instead')]
    public function readBytesWithLength(bool $use16BitLength = false): BinaryString
    {
        return $this->readBytesWith($use16BitLength ? IntType::UINT16 : IntType::UINT8);
    }

    #[Deprecated('Use readStringWith(length: IntType::UINT8) or readStringWith(length: IntType::UINT16) instead')]
    public function readStringWithLength(bool $use16BitLength = false): BinaryString
    {
        return $this->readStringWith($use16BitLength ? IntType::UINT16 : IntType::UINT8);
    }
}
