<?php declare(strict_types=1);

namespace KDuma\BinaryTools\Tests;

use KDuma\BinaryTools\BinaryReader;
use KDuma\BinaryTools\BinaryString;
use KDuma\BinaryTools\IntType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BinaryReader::class)]
class BinaryReaderTest extends TestCase
{
    private BinaryReader $reader;

    protected function setUp(): void
    {
        $this->reader = new BinaryReader(BinaryString::fromString("\x01\x02\x03\x04"));

        parent::setUp();
    }

    public function testReadByte()
    {
        $this->assertEquals(0x01, $this->reader->readByte());
        $this->assertEquals(1, $this->reader->position);

        $this->reader->seek($this->reader->length);
        try {
            $this->reader->readByte();
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while reading byte', $exception->getMessage());
            $this->assertEquals($this->reader->length, $this->reader->position);
        }
    }

    public function testReadBytes()
    {
        $this->assertEquals(BinaryString::fromString("\x01\x02\x03"), $this->reader->readBytes(3));
        $this->assertEquals(3, $this->reader->position);


        $this->reader->seek($this->reader->length);
        try {
            $this->reader->readBytes(3);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while reading 3 bytes', $exception->getMessage());
            $this->assertEquals($this->reader->length, $this->reader->position);
        }

        $this->reader->seek($this->reader->length - 2);
        try {
            $this->reader->readBytes(3);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while reading 3 bytes', $exception->getMessage());
            $this->assertEquals($this->reader->length - 2, $this->reader->position);
        }
    }

    public function testReadBytesWithLength()
    {
        $this->reader = new BinaryReader(BinaryString::fromString("\x00\x02\x03\x04"));

        $this->reader->seek(1);
        $this->assertEquals(BinaryString::fromString("\x03\x04"), $this->reader->readBytesWithLength());
        $this->assertEquals(4, $this->reader->position);

        $this->reader->seek(0);
        $this->assertEquals(BinaryString::fromString("\x03\x04"), $this->reader->readBytesWithLength(true));
        $this->assertEquals(4, $this->reader->position);

        $this->reader->seek(0);
        $this->assertEquals(BinaryString::fromString(""), $this->reader->readBytesWithLength());
        $this->assertEquals(1, $this->reader->position);

        try {
            $this->reader->seek(2);
            $this->reader->readBytesWithLength();
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while reading 3 bytes', $exception->getMessage());
            $this->assertEquals(2, $this->reader->position);
        }

        try {
            $this->reader->seek(1);
            $this->reader->readBytesWithLength(true);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while reading 515 bytes', $exception->getMessage());
            $this->assertEquals(1, $this->reader->position);
        }
    }

    public static function readIntProvider(): iterable
    {
        yield 'uint8 max' => [255, "\xFF", IntType::UINT8];
        yield 'int8 positive' => [127, "\x7F", IntType::INT8];
        yield 'int8 negative' => [-1, "\xFF", IntType::INT8];
        yield 'uint16 positive' => [1234, "\x04\xD2", IntType::UINT16];
        yield 'uint16 little endian positive' => [1234, "\xD2\x04", IntType::UINT16_LE];
        yield 'int16 positive' => [1234, "\x04\xD2", IntType::INT16];
        yield 'int16 little endian positive' => [1234, "\xD2\x04", IntType::INT16_LE];
        yield 'int16 negative' => [-1234, "\xFB\x2E", IntType::INT16];
        yield 'int16 little endian negative' => [-1234, "\x2E\xFB", IntType::INT16_LE];
        yield 'uint32 positive' => [0xDEADBEEF, "\xDE\xAD\xBE\xEF", IntType::UINT32];
        yield 'uint32 little endian positive' => [0xDEADBEEF, "\xEF\xBE\xAD\xDE", IntType::UINT32_LE];
        yield 'int32 positive' => [1234, "\x00\x00\x04\xD2", IntType::INT32];
        yield 'int32 little endian positive' => [1234, "\xD2\x04\x00\x00", IntType::INT32_LE];
        yield 'int32 negative' => [-1234, "\xFF\xFF\xFB\x2E", IntType::INT32];
        yield 'int32 little endian negative' => [-1234, "\x2E\xFB\xFF\xFF", IntType::INT32_LE];
        yield 'uint64 positive' => [0x0123456789ABCDEF, "\x01\x23\x45\x67\x89\xAB\xCD\xEF", IntType::UINT64];
        yield 'uint64 little endian positive' => [0x0123456789ABCDEF, "\xEF\xCD\xAB\x89\x67\x45\x23\x01", IntType::UINT64_LE];
        yield 'int64 positive' => [1234, "\x00\x00\x00\x00\x00\x00\x04\xD2", IntType::INT64];
        yield 'int64 little endian positive' => [1234, "\xD2\x04\x00\x00\x00\x00\x00\x00", IntType::INT64_LE];
        yield 'int64 negative' => [-1234, "\xFF\xFF\xFF\xFF\xFF\xFF\xFB\x2E", IntType::INT64];
        yield 'int64 little endian negative' => [-1234, "\x2E\xFB\xFF\xFF\xFF\xFF\xFF\xFF", IntType::INT64_LE];
    }

    /**
     * @dataProvider readIntProvider
     */
    public function testReadInt(int $expected, string $payload, IntType $type): void
    {
        $this->reader = new BinaryReader(BinaryString::fromString($payload));

        $this->assertSame($expected, $this->reader->readInt($type));
        $this->assertSame(strlen($payload), $this->reader->position);
    }

    public function testReadIntUnsupportedSize(): void
    {
        // This test only runs on 32-bit systems where 64-bit integers are not supported
        if (PHP_INT_SIZE >= 8) {
            $this->markTestSkipped('64-bit integers are supported on this platform');
        }

        $this->reader = new BinaryReader(BinaryString::fromString("\x01\x23\x45\x67\x89\xAB\xCD\xEF"));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot read 8-byte integers on 4-byte platform');

        $this->reader->readInt(IntType::UINT64);
    }

    public function testReadUint16BEDeprecated(): void
    {
        $this->reader = new BinaryReader(BinaryString::fromString("\x04\xD2"));

        $this->assertSame(1234, $this->reader->readUint16BE());
        $this->assertSame(2, $this->reader->position);
    }

    public function testPeekByte()
    {
        $this->assertEquals(0x01, $this->reader->peekByte());
        $this->assertEquals(0, $this->reader->position);

        try {
            $this->reader->seek($this->reader->length);
            $this->reader->peekByte();
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while peeking byte', $exception->getMessage());
            $this->assertEquals($this->reader->length, $this->reader->position);
        }
    }

    public function testSkip()
    {
        $this->reader->skip(2);
        $this->assertEquals(0x03, $this->reader->peekByte());
        $this->assertEquals(2, $this->reader->position);

        try {
            $this->reader->seek($this->reader->length);
            $this->reader->skip(2);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Cannot skip 2 bytes, not enough data', $exception->getMessage());
            $this->assertEquals($this->reader->length, $this->reader->position);
        }

        try {
            $this->reader->seek($this->reader->length - 2);
            $this->reader->skip(3);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Cannot skip 3 bytes, not enough data', $exception->getMessage());
            $this->assertEquals($this->reader->length - 2, $this->reader->position);
        }
    }

    public function testPeekBytes()
    {
        $this->assertEquals(BinaryString::fromString("\x01\x02\x03"), $this->reader->peekBytes(3));
        $this->assertEquals(0, $this->reader->position);


        try {
            $this->reader->seek($this->reader->length);
            $this->reader->peekBytes(3);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while peeking 3 bytes', $exception->getMessage());
            $this->assertEquals($this->reader->length, $this->reader->position);
        }

        try {
            $this->reader->seek($this->reader->length - 2);
            $this->reader->peekBytes(3);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while peeking 3 bytes', $exception->getMessage());
            $this->assertEquals($this->reader->length - 2, $this->reader->position);
        }
    }

    public function testReadString()
    {
        $this->reader = new BinaryReader(BinaryString::fromString("TEST"));
        $this->assertEquals(BinaryString::fromString("TEST"), $this->reader->readString(4));
        $this->assertEquals(4, $this->reader->position);

        try {
            $this->reader->seek(0);
            $this->reader->readString(5);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while reading 5 bytes', $exception->getMessage());
            $this->assertEquals(0, $this->reader->position);
        }

        $this->reader = new BinaryReader(BinaryString::fromString("\xFF\xFF"));
        try {
            $this->reader->seek(0);
            $this->reader->readString(2);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Invalid UTF-8 string', $exception->getMessage());
            $this->assertEquals(0, $this->reader->position);
        }
    }


    public function testReadStringWithLength()
    {
        $this->reader = new BinaryReader(BinaryString::fromString("\x00\x03TEST"));

        $this->reader->seek(1);
        $this->assertEquals(BinaryString::fromString("TES"), $this->reader->readStringWithLength());
        $this->assertEquals(5, $this->reader->position);

        $this->reader->seek(0);
        $this->assertEquals(BinaryString::fromString("TES"), $this->reader->readStringWithLength(true));
        $this->assertEquals(5, $this->reader->position);

        $this->reader->seek(0);
        $this->assertEquals(BinaryString::fromString(""), $this->reader->readStringWithLength());
        $this->assertEquals(1, $this->reader->position);

        try {
            $this->reader->seek(2);
            $this->reader->readStringWithLength();
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while reading 84 bytes', $exception->getMessage());
            $this->assertEquals(2, $this->reader->position);
        }

        try {
            $this->reader->seek(1);
            $this->reader->readStringWithLength(true);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Unexpected end of data while reading 852 bytes', $exception->getMessage());
            $this->assertEquals(1, $this->reader->position);
        }

        $this->reader = new BinaryReader(BinaryString::fromString("\x00\x03T\xFFEST"));

        try {
            $this->reader->seek(1);
            $this->reader->readStringWithLength();
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Invalid UTF-8 string', $exception->getMessage());
            $this->assertEquals(1, $this->reader->position);
        }

        try {
            $this->reader->seek(0);
            $this->reader->readStringWithLength(true);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Invalid UTF-8 string', $exception->getMessage());
            $this->assertEquals(0, $this->reader->position);
        }
    }

    public function testGetRemainingData()
    {
        $this->reader->seek(1);
        $this->assertEquals(BinaryString::fromString("\x02\x03\x04"), $this->reader->remaining_data);
        $this->assertEquals(1, $this->reader->position);

        $this->reader->seek(4);
        $this->assertEquals(BinaryString::fromString(""), $this->reader->remaining_data);
        $this->assertEquals(4, $this->reader->position);
    }

    public function testGetData()
    {
        $this->reader->seek(1);
        $this->assertEquals(BinaryString::fromString("\x01\x02\x03\x04"), $this->reader->data);
        $this->assertEquals(1, $this->reader->position);
    }

    public function testHasMoreData()
    {
        $this->reader->seek(0);
        $this->assertTrue($this->reader->has_more_data);

        $this->reader->seek(4);
        $this->assertFalse($this->reader->has_more_data);
    }

    public function testSeek()
    {
        $this->reader->seek(2);
        $this->assertEquals(2, $this->reader->position);

        $this->reader->seek(4);
        $this->assertEquals(4, $this->reader->position);

        try {
            $this->reader->seek(5);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Invalid seek position: 5', $exception->getMessage());
            $this->assertEquals(4, $this->reader->position);
        }

        try {
            $this->reader->seek(-1);
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Invalid seek position: -1', $exception->getMessage());
            $this->assertEquals(4, $this->reader->position);
        }
    }

    public function testGetPosition()
    {
        $this->assertEquals(0, $this->reader->position);
        $this->reader->seek(2);
        $this->assertEquals(2, $this->reader->position);
    }

    public function testSetPosition()
    {
        // Test direct position property assignment
        $this->reader->position = 2;
        $this->assertEquals(2, $this->reader->position);

        // Test validation through direct assignment
        try {
            $this->reader->position = -1;
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Invalid seek position: -1', $exception->getMessage());
        }

        try {
            $this->reader->position = 5;
            $this->fail("Expected exception not thrown");
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Invalid seek position: 5', $exception->getMessage());
        }
    }

    public function testGetRemainingBytes()
    {
        $this->reader->seek(0);
        $this->assertEquals(4, $this->reader->remaining_bytes);

        $this->reader->seek(2);
        $this->assertEquals(2, $this->reader->remaining_bytes);

        $this->reader->seek(4);
        $this->assertEquals(0, $this->reader->remaining_bytes);
    }

    /**
     * @return array<string, array{data: string, length: IntType, expected: string}>
     */
    public static function readBytesWithProvider(): array
    {
        return [
            'UINT8 short' => ['data' => "\x03abc", 'length' => IntType::UINT8, 'expected' => 'abc'],
            'UINT8 empty' => ['data' => "\x00", 'length' => IntType::UINT8, 'expected' => ''],
            'UINT16 short' => ['data' => "\x00\x03abc", 'length' => IntType::UINT16, 'expected' => 'abc'],
            'UINT16_LE short' => ['data' => "\x03\x00abc", 'length' => IntType::UINT16_LE, 'expected' => 'abc'],
            'UINT32 short' => ['data' => "\x00\x00\x00\x03abc", 'length' => IntType::UINT32, 'expected' => 'abc'],
        ];
    }

    #[DataProvider('readBytesWithProvider')]
    public function testReadBytesWith(string $data, IntType $length, string $expected): void
    {
        $reader = new BinaryReader(BinaryString::fromString($data));
        $result = $reader->readBytesWith($length);
        $this->assertEquals($expected, $result->toString());
        $this->assertEquals(strlen($data), $reader->position);
    }

    #[DataProvider('readBytesWithProvider')]
    public function testReadStringWith(string $data, IntType $length, string $expected): void
    {
        $reader = new BinaryReader(BinaryString::fromString($data));
        $result = $reader->readStringWith($length);
        $this->assertEquals($expected, $result->toString());
        $this->assertEquals(strlen($data), $reader->position);
    }

    public function testReadBytesWithInsufficientData(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("\x05abc")); // Claims 5 bytes but only has 3

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected end of data while reading 5 bytes');

        $reader->readBytesWith(IntType::UINT8);
    }

    public function testReadStringWithInvalidUTF8(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("\x02\xFF\xFE")); // 2 bytes of invalid UTF-8

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid UTF-8 string');

        $reader->readStringWith(IntType::UINT8);
    }

    public function testReadBytesWithLengthDeprecated(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("\x03abc"));
        $result = $reader->readBytesWithLength();
        $this->assertEquals('abc', $result->toString());

        $reader = new BinaryReader(BinaryString::fromString("\x00\x03abc"));
        $result = $reader->readBytesWithLength(true);
        $this->assertEquals('abc', $result->toString());
    }

    public function testReadStringWithLengthDeprecated(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("\x03abc"));
        $result = $reader->readStringWithLength();
        $this->assertEquals('abc', $result->toString());

        $reader = new BinaryReader(BinaryString::fromString("\x00\x03abc"));
        $result = $reader->readStringWithLength(true);
        $this->assertEquals('abc', $result->toString());
    }

    public function testRoundTripCompatibility(): void
    {
        $writer = new \KDuma\BinaryTools\BinaryWriter();
        $testData = BinaryString::fromString("Hello, World!");

        // Test UINT8
        $writer->writeBytesWith($testData, IntType::UINT8);
        $reader = new BinaryReader($writer->getBuffer());
        $result = $reader->readBytesWith(IntType::UINT8);
        $this->assertTrue($testData->equals($result));

        // Test UINT16
        $writer->reset();
        $writer->writeBytesWith($testData, IntType::UINT16);
        $reader = new BinaryReader($writer->getBuffer());
        $result = $reader->readBytesWith(IntType::UINT16);
        $this->assertTrue($testData->equals($result));
    }

    public function testReadIntUnsignedOverflow(): void
    {
        // Test UINT64 overflow detection on 64-bit systems
        if (PHP_INT_SIZE < 8) {
            $this->markTestSkipped('64-bit integers are not supported on this platform');
        }

        $reader = new BinaryReader(BinaryString::fromHex("FFFFFFFFFFFFFFFF")); // Would wrap to -1

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Value exceeds maximum for UINT64 on this platform');

        $reader->readInt(IntType::UINT64);
    }
}
