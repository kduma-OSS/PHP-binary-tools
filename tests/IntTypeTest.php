<?php declare(strict_types=1);

namespace KDuma\BinaryTools\Tests;

use KDuma\BinaryTools\IntType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IntType::class)]
class IntTypeTest extends TestCase
{
    /**
     * @return array<string, array{type: IntType, bytes: int}>
     */
    public static function bytesProvider(): array
    {
        return [
            'UINT8' => ['type' => IntType::UINT8, 'bytes' => 1],
            'INT8' => ['type' => IntType::INT8, 'bytes' => 1],
            'UINT16' => ['type' => IntType::UINT16, 'bytes' => 2],
            'INT16' => ['type' => IntType::INT16, 'bytes' => 2],
            'UINT16_LE' => ['type' => IntType::UINT16_LE, 'bytes' => 2],
            'INT16_LE' => ['type' => IntType::INT16_LE, 'bytes' => 2],
            'UINT32' => ['type' => IntType::UINT32, 'bytes' => 4],
            'INT32' => ['type' => IntType::INT32, 'bytes' => 4],
            'UINT32_LE' => ['type' => IntType::UINT32_LE, 'bytes' => 4],
            'INT32_LE' => ['type' => IntType::INT32_LE, 'bytes' => 4],
            'UINT64' => ['type' => IntType::UINT64, 'bytes' => 8],
            'INT64' => ['type' => IntType::INT64, 'bytes' => 8],
            'UINT64_LE' => ['type' => IntType::UINT64_LE, 'bytes' => 8],
            'INT64_LE' => ['type' => IntType::INT64_LE, 'bytes' => 8],
        ];
    }

    #[DataProvider('bytesProvider')]
    public function testBytes(IntType $type, int $bytes): void
    {
        $this->assertSame($bytes, $type->bytes());
    }

    /**
     * @return array<string, array{type: IntType, signed: bool}>
     */
    public static function signedProvider(): array
    {
        return [
            'UINT8' => ['type' => IntType::UINT8, 'signed' => false],
            'INT8' => ['type' => IntType::INT8, 'signed' => true],
            'UINT16' => ['type' => IntType::UINT16, 'signed' => false],
            'INT16' => ['type' => IntType::INT16, 'signed' => true],
            'UINT16_LE' => ['type' => IntType::UINT16_LE, 'signed' => false],
            'INT16_LE' => ['type' => IntType::INT16_LE, 'signed' => true],
            'UINT32' => ['type' => IntType::UINT32, 'signed' => false],
            'INT32' => ['type' => IntType::INT32, 'signed' => true],
            'UINT32_LE' => ['type' => IntType::UINT32_LE, 'signed' => false],
            'INT32_LE' => ['type' => IntType::INT32_LE, 'signed' => true],
            'UINT64' => ['type' => IntType::UINT64, 'signed' => false],
            'INT64' => ['type' => IntType::INT64, 'signed' => true],
            'UINT64_LE' => ['type' => IntType::UINT64_LE, 'signed' => false],
            'INT64_LE' => ['type' => IntType::INT64_LE, 'signed' => true],
        ];
    }

    #[DataProvider('signedProvider')]
    public function testIsSigned(IntType $type, bool $signed): void
    {
        $this->assertSame($signed, $type->isSigned());
    }

    /**
     * @return array<string, array{type: IntType, littleEndian: bool}>
     */
    public static function endianProvider(): array
    {
        return [
            'UINT8' => ['type' => IntType::UINT8, 'littleEndian' => false],
            'INT8' => ['type' => IntType::INT8, 'littleEndian' => false],
            'UINT16' => ['type' => IntType::UINT16, 'littleEndian' => false],
            'INT16' => ['type' => IntType::INT16, 'littleEndian' => false],
            'UINT16_LE' => ['type' => IntType::UINT16_LE, 'littleEndian' => true],
            'INT16_LE' => ['type' => IntType::INT16_LE, 'littleEndian' => true],
            'UINT32' => ['type' => IntType::UINT32, 'littleEndian' => false],
            'INT32' => ['type' => IntType::INT32, 'littleEndian' => false],
            'UINT32_LE' => ['type' => IntType::UINT32_LE, 'littleEndian' => true],
            'INT32_LE' => ['type' => IntType::INT32_LE, 'littleEndian' => true],
            'UINT64' => ['type' => IntType::UINT64, 'littleEndian' => false],
            'INT64' => ['type' => IntType::INT64, 'littleEndian' => false],
            'UINT64_LE' => ['type' => IntType::UINT64_LE, 'littleEndian' => true],
            'INT64_LE' => ['type' => IntType::INT64_LE, 'littleEndian' => true],
        ];
    }

    #[DataProvider('endianProvider')]
    public function testIsLittleEndian(IntType $type, bool $littleEndian): void
    {
        $this->assertSame($littleEndian, $type->isLittleEndian());
    }

    /**
     * @return array<string, array{type: IntType, supported: bool}>
     */
    public static function supportedProvider(): array
    {
        $is64Bit = PHP_INT_SIZE >= 8;

        return [
            'UINT8' => ['type' => IntType::UINT8, 'supported' => true],
            'INT8' => ['type' => IntType::INT8, 'supported' => true],
            'UINT16' => ['type' => IntType::UINT16, 'supported' => true],
            'INT16' => ['type' => IntType::INT16, 'supported' => true],
            'UINT16_LE' => ['type' => IntType::UINT16_LE, 'supported' => true],
            'INT16_LE' => ['type' => IntType::INT16_LE, 'supported' => true],
            'UINT32' => ['type' => IntType::UINT32, 'supported' => true],
            'INT32' => ['type' => IntType::INT32, 'supported' => true],
            'UINT32_LE' => ['type' => IntType::UINT32_LE, 'supported' => true],
            'INT32_LE' => ['type' => IntType::INT32_LE, 'supported' => true],
            'UINT64' => ['type' => IntType::UINT64, 'supported' => $is64Bit],
            'INT64' => ['type' => IntType::INT64, 'supported' => $is64Bit],
            'UINT64_LE' => ['type' => IntType::UINT64_LE, 'supported' => $is64Bit],
            'INT64_LE' => ['type' => IntType::INT64_LE, 'supported' => $is64Bit],
        ];
    }

    #[DataProvider('supportedProvider')]
    public function testIsSupported(IntType $type, bool $supported): void
    {
        $this->assertSame($supported, $type->isSupported());
    }

    /**
     * @return array<string, array{type: IntType, minValue: int}>
     */
    public static function minValueProvider(): array
    {
        return [
            'UINT8' => ['type' => IntType::UINT8, 'minValue' => 0],
            'INT8' => ['type' => IntType::INT8, 'minValue' => -128],
            'UINT16' => ['type' => IntType::UINT16, 'minValue' => 0],
            'INT16' => ['type' => IntType::INT16, 'minValue' => -32768],
            'UINT16_LE' => ['type' => IntType::UINT16_LE, 'minValue' => 0],
            'INT16_LE' => ['type' => IntType::INT16_LE, 'minValue' => -32768],
            'UINT32' => ['type' => IntType::UINT32, 'minValue' => 0],
            'INT32' => ['type' => IntType::INT32, 'minValue' => -2147483648],
            'UINT32_LE' => ['type' => IntType::UINT32_LE, 'minValue' => 0],
            'INT32_LE' => ['type' => IntType::INT32_LE, 'minValue' => -2147483648],
            'UINT64' => ['type' => IntType::UINT64, 'minValue' => 0],
            'INT64' => ['type' => IntType::INT64, 'minValue' => PHP_INT_MIN],
            'UINT64_LE' => ['type' => IntType::UINT64_LE, 'minValue' => 0],
            'INT64_LE' => ['type' => IntType::INT64_LE, 'minValue' => PHP_INT_MIN],
        ];
    }

    #[DataProvider('minValueProvider')]
    public function testMinValue(IntType $type, int $minValue): void
    {
        $this->assertSame($minValue, $type->minValue());
    }

    /**
     * @return array<string, array{type: IntType, maxValue: int}>
     */
    public static function maxValueProvider(): array
    {
        $is64Bit = PHP_INT_SIZE >= 8;

        return [
            'UINT8' => ['type' => IntType::UINT8, 'maxValue' => 255],
            'INT8' => ['type' => IntType::INT8, 'maxValue' => 127],
            'UINT16' => ['type' => IntType::UINT16, 'maxValue' => 65535],
            'INT16' => ['type' => IntType::INT16, 'maxValue' => 32767],
            'UINT16_LE' => ['type' => IntType::UINT16_LE, 'maxValue' => 65535],
            'INT16_LE' => ['type' => IntType::INT16_LE, 'maxValue' => 32767],
            'UINT32' => ['type' => IntType::UINT32, 'maxValue' => 4294967295],
            'INT32' => ['type' => IntType::INT32, 'maxValue' => 2147483647],
            'UINT32_LE' => ['type' => IntType::UINT32_LE, 'maxValue' => 4294967295],
            'INT32_LE' => ['type' => IntType::INT32_LE, 'maxValue' => 2147483647],
            'UINT64' => ['type' => IntType::UINT64, 'maxValue' => PHP_INT_MAX], // Limited by PHP_INT_MAX
            'INT64' => ['type' => IntType::INT64, 'maxValue' => PHP_INT_MAX],
            'UINT64_LE' => ['type' => IntType::UINT64_LE, 'maxValue' => PHP_INT_MAX],
            'INT64_LE' => ['type' => IntType::INT64_LE, 'maxValue' => PHP_INT_MAX],
        ];
    }

    #[DataProvider('maxValueProvider')]
    public function testMaxValue(IntType $type, int $maxValue): void
    {
        $this->assertSame($maxValue, $type->maxValue());
    }

    /**
     * @return array<string, array{type: IntType, validValue: int, belowMin: int, aboveMax: int}>
     */
    public static function isValidProvider(): array
    {
        return [
            'UINT8' => ['type' => IntType::UINT8, 'validValue' => 100, 'belowMin' => -1, 'aboveMax' => 256],
            'INT8' => ['type' => IntType::INT8, 'validValue' => 50, 'belowMin' => -129, 'aboveMax' => 128],
            'UINT16' => ['type' => IntType::UINT16, 'validValue' => 1000, 'belowMin' => -1, 'aboveMax' => 65536],
            'INT16' => ['type' => IntType::INT16, 'validValue' => 1000, 'belowMin' => -32769, 'aboveMax' => 32768],
            'UINT16_LE' => ['type' => IntType::UINT16_LE, 'validValue' => 1000, 'belowMin' => -1, 'aboveMax' => 65536],
            'INT16_LE' => ['type' => IntType::INT16_LE, 'validValue' => 1000, 'belowMin' => -32769, 'aboveMax' => 32768],
            'UINT32' => ['type' => IntType::UINT32, 'validValue' => 100000, 'belowMin' => -1, 'aboveMax' => 4294967296],
            'INT32' => ['type' => IntType::INT32, 'validValue' => 100000, 'belowMin' => -2147483649, 'aboveMax' => 2147483648],
            'UINT32_LE' => ['type' => IntType::UINT32_LE, 'validValue' => 100000, 'belowMin' => -1, 'aboveMax' => 4294967296],
            'INT32_LE' => ['type' => IntType::INT32_LE, 'validValue' => 100000, 'belowMin' => -2147483649, 'aboveMax' => 2147483648],
        ];
    }

    #[DataProvider('isValidProvider')]
    public function testIsValid(IntType $type, int $validValue, int $belowMin, int $aboveMax): void
    {
        // Test valid value
        $this->assertTrue($type->isValid($validValue));

        // Test boundary values
        $this->assertTrue($type->isValid($type->minValue()));
        $this->assertTrue($type->isValid($type->maxValue()));

        // Test invalid values (if they don't cause overflow issues)
        if ($belowMin > PHP_INT_MIN) {
            $this->assertFalse($type->isValid($belowMin));
        }
        if ($aboveMax < PHP_INT_MAX) {
            $this->assertFalse($type->isValid($aboveMax));
        }
    }

    public function testIsValidFor64BitTypes(): void
    {
        if (PHP_INT_SIZE < 8) {
            $this->markTestSkipped('64-bit integers not supported on this platform');
        }

        $types = [IntType::UINT64, IntType::INT64, IntType::UINT64_LE, IntType::INT64_LE];

        foreach ($types as $type) {
            // Test valid values
            $this->assertTrue($type->isValid(0));
            $this->assertTrue($type->isValid(1234));

            // Test boundary values
            $this->assertTrue($type->isValid($type->minValue()));
            $this->assertTrue($type->isValid($type->maxValue()));

            // For signed types, test negative values
            if ($type->isSigned()) {
                $this->assertTrue($type->isValid(-1234));
            }
        }
    }
}
