<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

enum IntType
{
    /** Unsigned 8-bit integer (0-255) - Single byte */
    case UINT8;

    /** Signed 8-bit integer (-128 to 127) - Single byte */
    case INT8;

    /** Unsigned 16-bit integer (0-65535) - Big-endian byte order */
    case UINT16;

    /** Signed 16-bit integer (-32768 to 32767) - Big-endian byte order */
    case INT16;

    /** Unsigned 32-bit integer (0-4294967295) - Big-endian byte order */
    case UINT32;

    /** Signed 32-bit integer (-2147483648 to 2147483647) - Big-endian byte order */
    case INT32;

    /** Unsigned 16-bit integer (0-65535) - Little-endian byte order */
    case UINT16_LE;

    /** Signed 16-bit integer (-32768 to 32767) - Little-endian byte order */
    case INT16_LE;

    /** Unsigned 32-bit integer (0-4294967295) - Little-endian byte order */
    case UINT32_LE;

    /** Signed 32-bit integer (-2147483648 to 2147483647) - Little-endian byte order */
    case INT32_LE;

    /** Unsigned 64-bit integer - Big-endian byte order (platform dependent range) */
    case UINT64;

    /** Signed 64-bit integer - Big-endian byte order (platform dependent range) */
    case INT64;

    /** Unsigned 64-bit integer - Little-endian byte order (platform dependent range) */
    case UINT64_LE;

    /** Signed 64-bit integer - Little-endian byte order (platform dependent range) */
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
