<?php declare(strict_types=1);

namespace KDuma\BinaryTools\Tests;

use KDuma\BinaryTools\BinaryReader;
use KDuma\BinaryTools\BinaryString;
use KDuma\BinaryTools\IntType;
use KDuma\BinaryTools\Terminator;
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

    public function testReadBytesWithTerminatorNUL(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("Hello\x00World"));
        $result = $reader->readBytesWith(terminator: Terminator::NUL);
        $this->assertEquals("Hello", $result->toString());
        $this->assertEquals(6, $reader->position); // 5 bytes + 1 terminator consumed
    }

    public function testReadBytesWithTerminatorGS(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("Data\x1DMore"));
        $result = $reader->readBytesWith(terminator: Terminator::GS);
        $this->assertEquals("Data", $result->toString());
        $this->assertEquals(5, $reader->position); // 4 bytes + 1 terminator consumed
    }

    public function testReadBytesWithCustomTerminator(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("Line 1\r\nLine 2"));
        $customTerminator = BinaryString::fromString("\r\n");
        $result = $reader->readBytesWith(terminator: $customTerminator);
        $this->assertEquals("Line 1", $result->toString());
        $this->assertEquals(8, $reader->position); // 6 bytes + 2 terminator bytes consumed
    }

    public function testReadStringWithTerminatorNUL(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("Hello World\x00Rest"));
        $result = $reader->readStringWith(terminator: Terminator::NUL);
        $this->assertEquals("Hello World", $result->toString());
        $this->assertEquals(12, $reader->position); // 11 bytes + 1 terminator consumed
    }

    public function testReadBytesWithTerminatorAtStart(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("\x00Data"));
        $result = $reader->readBytesWith(terminator: Terminator::NUL);
        $this->assertEquals("", $result->toString());
        $this->assertEquals(1, $reader->position); // Just the terminator consumed
    }

    public function testReadBytesWithMultipleTerminators(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("First\x00\x00Second"));

        // First read should stop at first terminator
        $result1 = $reader->readBytesWith(terminator: Terminator::NUL);
        $this->assertEquals("First", $result1->toString());
        $this->assertEquals(6, $reader->position);

        // Second read should get empty string (next char is terminator)
        $result2 = $reader->readBytesWith(terminator: Terminator::NUL);
        $this->assertEquals("", $result2->toString());
        $this->assertEquals(7, $reader->position);
    }

    public function testReadBytesWithNoTerminatorFound(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("NoTerminator"));
        $result = $reader->readBytesWith(optional_terminator: Terminator::NUL);
        $this->assertEquals("NoTerminator", $result->toString());
        $this->assertEquals(12, $reader->position); // All data consumed, no terminator found
    }

    public function testReadBytesWithMissingRequiredTerminatorThrows(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("Incomplete"));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Terminator not found before end of data');

        $reader->readBytesWith(terminator: Terminator::NUL);
    }

    public function testReadStringWithMissingRequiredTerminatorThrows(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("No terminator"));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Terminator not found before end of data');

        $reader->readStringWith(terminator: Terminator::NUL);
    }

    public function testReadStringWithTerminatorParameterValidation(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("test"));

        // Test both parameters provided
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one of length terminator or optional_terminator must be provided');

        $reader->readStringWith(IntType::UINT8, Terminator::NUL);
    }

    public function testReadBytesWithNoParameters(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("test"));

        // Test no parameters provided
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one of length terminator or optional_terminator must be provided');

        $reader->readBytesWith();
    }

    public function testReadBytesWithBothTerminatorsProvided(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("data"));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one of length terminator or optional_terminator must be provided');

        $reader->readBytesWith(terminator: Terminator::NUL, optional_terminator: Terminator::NUL);
    }

    public function testReadStringWithInvalidUTF8AndTerminator(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("\xFF\xFE\x00"));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid UTF-8 string');

        $reader->readStringWith(terminator: Terminator::NUL);
    }

    public function testReadStringWithOptionalTerminatorUntilEnd(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("Partial"));
        $result = $reader->readStringWith(optional_terminator: Terminator::NUL);

        $this->assertEquals("Partial", $result->toString());
        $this->assertEquals(7, $reader->position);
    }

    public function testRoundTripWithTerminators(): void
    {
        $writer = new \KDuma\BinaryTools\BinaryWriter();
        $testData = BinaryString::fromString("Hello, World!");

        // Test NUL terminator round trip
        $writer->writeBytesWith($testData, terminator: Terminator::NUL);
        $reader = new BinaryReader($writer->getBuffer());
        $result = $reader->readBytesWith(terminator: Terminator::NUL);
        $this->assertTrue($testData->equals($result));

        // Test custom terminator round trip
        $writer->reset();
        $customTerminator = BinaryString::fromString("||");
        $writer->writeStringWith($testData, terminator: $customTerminator);
        $reader = new BinaryReader($writer->getBuffer());
        $result = $reader->readStringWith(terminator: $customTerminator);
        $this->assertTrue($testData->equals($result));
    }

    public function testReadBytesWithCRLFTerminator(): void
    {
        $reader = new BinaryReader(BinaryString::fromString("HTTP/1.1 200 OK\x0D\x0AContent-Type: text/html"));
        $result = $reader->readBytesWith(terminator: Terminator::CRLF);
        $this->assertEquals("HTTP/1.1 200 OK", $result->toString());
        $this->assertEquals(17, $reader->position); // 15 chars + 2 CRLF bytes consumed
    }

    public function testReadStringWithCommonControlCharacters(): void
    {
        // Test with Line Feed
        $reader = new BinaryReader(BinaryString::fromString("Unix line\x0ANext line"));
        $result = $reader->readStringWith(terminator: Terminator::LF);
        $this->assertEquals("Unix line", $result->toString());
        $this->assertEquals(10, $reader->position);

        // Test with Tab separator
        $reader = new BinaryReader(BinaryString::fromString("field1\x09field2\x09field3"));
        $field1 = $reader->readBytesWith(terminator: Terminator::HT);
        $field2 = $reader->readBytesWith(terminator: Terminator::HT);
        $this->assertEquals("field1", $field1->toString());
        $this->assertEquals("field2", $field2->toString());

        // Test with Record Separator
        $reader = new BinaryReader(BinaryString::fromString("record1\x1Erecord2\x1E"));
        $rec1 = $reader->readStringWith(terminator: Terminator::RS);
        $rec2 = $reader->readStringWith(terminator: Terminator::RS);
        $this->assertEquals("record1", $rec1->toString());
        $this->assertEquals("record2", $rec2->toString());
    }

    public function testReadBytesWithProtocolSpecificTerminators(): void
    {
        // Test STX/ETX boundaries
        $reader = new BinaryReader(BinaryString::fromString("\x02Important Message\x03More data"));

        // Skip STX
        $stx = $reader->readBytesWith(terminator: Terminator::STX);
        $this->assertEquals("", $stx->toString());

        // Read message until ETX
        $message = $reader->readBytesWith(terminator: Terminator::ETX);
        $this->assertEquals("Important Message", $message->toString());

        // Test XON/XOFF flow control characters
        $reader = new BinaryReader(BinaryString::fromString("data\x11moredata\x13"));
        $beforeXon = $reader->readBytesWith(terminator: Terminator::DC1); // XON
        $beforeXoff = $reader->readBytesWith(terminator: Terminator::DC3); // XOFF
        $this->assertEquals("data", $beforeXon->toString());
        $this->assertEquals("moredata", $beforeXoff->toString());
    }

    public function testReadWithMultipleNewTerminators(): void
    {
        // Test multiple different terminators in sequence
        $data = "file1\x1Cgroup1\x1Drecord1\x1Eunit1\x1Fend\x20";
        $reader = new BinaryReader(BinaryString::fromString($data));

        $file = $reader->readStringWith(terminator: Terminator::FS);     // File Separator
        $group = $reader->readStringWith(terminator: Terminator::GS);    // Group Separator
        $record = $reader->readStringWith(terminator: Terminator::RS);   // Record Separator
        $unit = $reader->readStringWith(terminator: Terminator::US);     // Unit Separator
        $end = $reader->readStringWith(terminator: Terminator::SP);      // Space

        $this->assertEquals("file1", $file->toString());
        $this->assertEquals("group1", $group->toString());
        $this->assertEquals("record1", $record->toString());
        $this->assertEquals("unit1", $unit->toString());
        $this->assertEquals("end", $end->toString());
    }

    public function testCrlfVsLfTerminators(): void
    {
        // Test that CRLF and LF are handled differently
        $reader = new BinaryReader(BinaryString::fromString("line1\x0D\x0Aline2\x0Aline3"));

        // Read first line with CRLF
        $line1 = $reader->readStringWith(terminator: Terminator::CRLF);
        $this->assertEquals("line1", $line1->toString());

        // Read second line with just LF
        $line2 = $reader->readStringWith(terminator: Terminator::LF);
        $this->assertEquals("line2", $line2->toString());
    }
}
