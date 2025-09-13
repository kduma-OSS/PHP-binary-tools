<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

use RuntimeException;

class BinaryReader
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

    public function readBytesWithLength(bool $use16BitLength = false): BinaryString
    {
        if ($use16BitLength) {
            $length = $this->readUint16BE();
        } else {
            $length = $this->readByte();
        }

        try {
            return $this->readBytes($length);
        } catch (\RuntimeException $exception) {
            $this->position -= ($use16BitLength ? 2 : 1);
            throw $exception;
        }
    }

    public function readUint16BE(): int
    {
        $bytes = $this->readBytes(2)->value;
        return (ord($bytes[0]) << 8) | ord($bytes[1]);
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

    public function readStringWithLength(bool $use16BitLength = false): BinaryString
    {
        $string = $this->readBytesWithLength($use16BitLength);

        if (!mb_check_encoding($string->value, 'UTF-8')) {
            $this->position -= ($use16BitLength ? 2 : 1) + $string->size();
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
}
