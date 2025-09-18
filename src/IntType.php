<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

enum IntType
{
    case UINT8;
    case INT8;
    case UINT16;
    case INT16;
    case UINT32;
    case INT32;
    case UINT16_LE;
    case INT16_LE;
    case UINT32_LE;
    case INT32_LE;
    case UINT64;
    case INT64;
    case UINT64_LE;
    case INT64_LE;

    public function bytes(): int
    {
        return match ($this) {
            self::UINT8,
            self::INT8 => 1,
            self::UINT16,
            self::INT16,
            self::UINT16_LE,
            self::INT16_LE => 2,
            self::UINT32,
            self::INT32,
            self::UINT32_LE,
            self::INT32_LE => 4,
            self::UINT64,
            self::INT64,
            self::UINT64_LE,
            self::INT64_LE => 8,
        };
    }

    public function isSigned(): bool
    {
        return match ($this) {
            self::INT8,
            self::INT16,
            self::INT32,
            self::INT16_LE,
            self::INT32_LE,
            self::INT64,
            self::INT64_LE => true,
            default => false,
        };
    }

    public function isLittleEndian(): bool
    {
        return match ($this) {
            self::UINT16_LE,
            self::INT16_LE,
            self::UINT32_LE,
            self::INT32_LE,
            self::UINT64_LE,
            self::INT64_LE => true,
            default => false,
        };
    }

    public function isSupported(): bool
    {
        return $this->bytes() <= PHP_INT_SIZE;
    }

    public function minValue(): int
    {
        if ($this->isSigned()) {
            $bits = $this->bytes() * 8;
            if ($bits >= PHP_INT_SIZE * 8) {
                return PHP_INT_MIN;
            }
            return -(1 << ($bits - 1));
        }

        return 0;
    }

    public function maxValue(): int
    {
        $bits = $this->bytes() * 8;

        if ($this->isSigned()) {
            if ($bits >= PHP_INT_SIZE * 8) {
                return PHP_INT_MAX;
            }
            return (1 << ($bits - 1)) - 1;
        } else {
            if ($bits >= PHP_INT_SIZE * 8) {
                return PHP_INT_MAX;
            }
            return (1 << $bits) - 1;
        }
    }

    public function isValid(int $value): bool
    {
        return $value >= $this->minValue() && $value <= $this->maxValue();
    }
}
