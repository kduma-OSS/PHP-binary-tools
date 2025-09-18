<?php declare(strict_types=1);

namespace KDuma\BinaryTools\Tests;

use KDuma\BinaryTools\BinaryString;
use KDuma\BinaryTools\BinaryWriter;
use KDuma\BinaryTools\IntType;
use KDuma\BinaryTools\Terminator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BinaryWriter::class)]
class BinaryWriterTest extends TestCase
{
    private BinaryWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new BinaryWriter();

        $this->writer->writeByte(0x01);
        $this->writer->writeByte(0x02);
        $this->writer->writeByte(0x03);
        $this->writer->writeByte(0x04);

        parent::setUp();
    }

    public function testReset()
    {
        $this->writer->reset();
        $this->assertEquals(0, $this->writer->getLength());
    }

    public function testWriteBytes()
    {
        $this->writer->reset();
        $this->writer->writeBytes(BinaryString::fromString("\x05\x06\x07"));
        $this->assertEquals("\x05\x06\x07", $this->writer->getBuffer()->toString());
    }

    public function testGetBuffer()
    {
        $buffer = $this->writer->getBuffer();
        $this->assertEquals("\x01\x02\x03\x04", $buffer->toString());
    }

    public function testGetLength()
    {
        $this->assertEquals(4, $this->writer->getLength());
    }

    public function testWriteUint16BE()
    {
        $this->writer->reset();
        $this->writer->writeUint16BE(0x1234);
        $this->assertEquals("\x12\x34", $this->writer->getBuffer()->toString());

        $this->writer->reset();
        $this->writer->writeUint16BE(65535);
        $this->assertEquals("\xff\xff", $this->writer->getBuffer()->toString());

        try {
            $this->writer->reset();
            $this->writer->writeUint16BE(65535 + 1);
            $this->fail("Expected exception not thrown");
        } catch (\InvalidArgumentException $exception) {
            $this->assertEquals('Value 65536 is out of range for UINT16', $exception->getMessage());
            $this->assertEquals(0, $this->writer->getLength());
        }
    }

    public function testWriteByte()
    {
        $this->writer->reset();
        $this->writer->writeByte(0x05);
        $this->assertEquals("\x05", $this->writer->getBuffer()->toString());

        $this->writer->reset();
        $this->writer->writeByte(0x00);
        $this->assertEquals("\x00", $this->writer->getBuffer()->toString());

        $this->writer->reset();
        $this->writer->writeByte(0xFF);
        $this->assertEquals("\xff", $this->writer->getBuffer()->toString());


        try {
            $this->writer->reset();
            $this->writer->writeByte(255 + 1);
            $this->fail("Expected exception not thrown");
        } catch (\InvalidArgumentException $exception) {
            $this->assertEquals('Byte value must be between 0 and 255', $exception->getMessage());
            $this->assertEquals(0, $this->writer->getLength());
        }

        try {
            $this->writer->reset();
            $this->writer->writeByte(-1);
            $this->fail("Expected exception not thrown");
        } catch (\InvalidArgumentException $exception) {
            $this->assertEquals('Byte value must be between 0 and 255', $exception->getMessage());
            $this->assertEquals(0, $this->writer->getLength());
        }

        try {
            $this->writer->reset();
            $this->writer->writeByte(-256);
            $this->fail("Expected exception not thrown");
        } catch (\InvalidArgumentException $exception) {
            $this->assertEquals('Byte value must be between 0 and 255', $exception->getMessage());
            $this->assertEquals(0, $this->writer->getLength());
        }
    }

    public function testWriteBytesWithLength()
    {
        $this->writer->reset();
        $this->writer->writeBytesWithLength(BinaryString::fromString("\x05\x06\x07"));
        $this->assertEquals("\x03\x05\x06\x07", $this->writer->getBuffer()->toString());

        $this->writer->reset();
        $this->writer->writeBytesWithLength(BinaryString::fromString("\x05\x06\x07"), true);
        $this->assertEquals("\x00\x03\x05\x06\x07", $this->writer->getBuffer()->toString());

        $this->writer->reset();
        $this->writer->writeBytesWithLength(BinaryString::fromString(str_repeat("\x00", 255)));
        $this->assertEquals(1 + 255, $this->writer->getLength());

        try {
            $this->writer->reset();
            $this->writer->writeBytesWithLength(BinaryString::fromString(str_repeat("\x00", 255 + 1)));
            $this->fail("Expected exception not thrown");
        } catch (\InvalidArgumentException $exception) {
            $this->assertEquals('Data too long for UINT8 length field (max: 255)', $exception->getMessage());
            $this->assertEquals(0, $this->writer->getLength());
        }

        $this->writer->reset();
        $this->writer->writeBytesWithLength(BinaryString::fromString(str_repeat("\x00", 65535)), true);
        $this->assertEquals(2 + 65535, $this->writer->getLength());

        try {
            $this->writer->reset();
            $this->writer->writeBytesWithLength(BinaryString::fromString(str_repeat("\x00", 65535 + 1)), true);
            $this->fail("Expected exception not thrown");
        } catch (\InvalidArgumentException $exception) {
            $this->assertEquals('Data too long for UINT16 length field (max: 65535)', $exception->getMessage());
            $this->assertEquals(0, $this->writer->getLength());
        }
    }

    public function testWriteStringWithLength()
    {
        $this->writer->reset();
        $this->writer->writeStringWithLength(BinaryString::fromString("abc"));
        $this->assertEquals("\x03abc", $this->writer->getBuffer()->toString());

        try {
            $this->writer->reset();
            $this->writer->writeStringWithLength(BinaryString::fromString("\x00\xFF")); // Invalid UTF-8
            $this->fail("Expected exception not thrown");
        } catch (\InvalidArgumentException $exception) {
            $this->assertEquals('String must be valid UTF-8', $exception->getMessage());
            $this->assertEquals(0, $this->writer->getLength());
        }
    }

    public function testWriteString()
    {
        $this->writer->reset();
        $this->writer->writeString(BinaryString::fromString("abc"));
        $this->assertEquals("abc", $this->writer->getBuffer()->toString());

        try {
            $this->writer->reset();
            $this->writer->writeString(BinaryString::fromString("\x00\xFF")); // Invalid UTF-8
            $this->fail("Expected exception not thrown");
        } catch (\InvalidArgumentException $exception) {
            $this->assertEquals('String must be valid UTF-8', $exception->getMessage());
            $this->assertEquals(0, $this->writer->getLength());
        }
    }

    public static function writeIntProvider(): iterable
    {
        yield 'uint8 zero' => [0, "\x00", IntType::UINT8];
        yield 'uint8 max' => [255, "\xFF", IntType::UINT8];
        yield 'int8 positive' => [127, "\x7F", IntType::INT8];
        yield 'int8 negative' => [-1, "\xFF", IntType::INT8];
        yield 'int8 min' => [-128, "\x80", IntType::INT8];
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
     * @dataProvider writeIntProvider
     */
    public function testWriteInt(int $value, string $expected, IntType $type): void
    {
        if (!$type->isSupported()) {
            $this->markTestSkipped(sprintf('IntType %s is not supported on this platform', $type->name));
        }

        $this->writer->reset();
        $this->writer->writeInt($type, $value);
        $this->assertEquals($expected, $this->writer->getBuffer()->toString());
    }

    public function testWriteIntUnsupportedType(): void
    {
        // This test only runs on 32-bit systems where 64-bit integers are not supported
        if (PHP_INT_SIZE >= 8) {
            $this->markTestSkipped('64-bit integers are supported on this platform');
        }

        $this->writer->reset();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot write 8-byte integers on 4-byte platform');

        $this->writer->writeInt(IntType::UINT64, 1234);
    }

    public function testWriteIntOutOfRange(): void
    {
        $this->writer->reset();

        // Test uint8 overflow
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value 256 is out of range for UINT8');

        $this->writer->writeInt(IntType::UINT8, 256);
    }

    public function testWriteIntNegativeUnsigned(): void
    {
        $this->writer->reset();

        // Test negative value for unsigned type
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value -1 is out of range for UINT8');

        $this->writer->writeInt(IntType::UINT8, -1);
    }

    public function testWriteIntSignedOverflow(): void
    {
        $this->writer->reset();

        // Test int8 overflow
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value 128 is out of range for INT8');

        $this->writer->writeInt(IntType::INT8, 128);
    }

    public function testWriteIntSignedUnderflow(): void
    {
        $this->writer->reset();

        // Test int8 underflow
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value -129 is out of range for INT8');

        $this->writer->writeInt(IntType::INT8, -129);
    }

    public function testWriteUint16BEDeprecated(): void
    {
        $this->writer->reset();
        $this->writer->writeUint16BE(1234);
        $this->assertEquals("\x04\xD2", $this->writer->getBuffer()->toString());
    }

    /**
     * @return array<string, array{data: string, length: IntType, expected: string}>
     */
    public static function writeBytesWithProvider(): array
    {
        return [
            'UINT8 short' => ['data' => 'abc', 'length' => IntType::UINT8, 'expected' => "\x03abc"],
            'UINT8 empty' => ['data' => '', 'length' => IntType::UINT8, 'expected' => "\x00"],
            'UINT16 short' => ['data' => 'abc', 'length' => IntType::UINT16, 'expected' => "\x00\x03abc"],
            'UINT16_LE short' => ['data' => 'abc', 'length' => IntType::UINT16_LE, 'expected' => "\x03\x00abc"],
            'UINT32 short' => ['data' => 'abc', 'length' => IntType::UINT32, 'expected' => "\x00\x00\x00\x03abc"],
        ];
    }

    #[DataProvider('writeBytesWithProvider')]
    public function testWriteBytesWith(string $data, IntType $length, string $expected): void
    {
        $this->writer->reset();
        $this->writer->writeBytesWith(BinaryString::fromString($data), $length);
        $this->assertEquals($expected, $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithOverflow(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString(str_repeat('a', 256));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data too long for UINT8 length field (max: 255)');

        $this->writer->writeBytesWith($data, IntType::UINT8);
    }

    #[DataProvider('writeBytesWithProvider')]
    public function testWriteStringWith(string $data, IntType $length, string $expected): void
    {
        $this->writer->reset();
        $this->writer->writeStringWith(BinaryString::fromString($data), $length);
        $this->assertEquals($expected, $this->writer->getBuffer()->toString());
    }

    public function testWriteStringWithInvalidUTF8(): void
    {
        $this->writer->reset();
        $invalidUTF8 = BinaryString::fromString("\xFF\xFE");

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String must be valid UTF-8');

        $this->writer->writeStringWith($invalidUTF8, IntType::UINT8);
    }

    public function testWriteBytesWithLengthDeprecated(): void
    {
        $this->writer->reset();
        $this->writer->writeBytesWithLength(BinaryString::fromString("abc"));
        $this->assertEquals("\x03abc", $this->writer->getBuffer()->toString());

        $this->writer->reset();
        $this->writer->writeBytesWithLength(BinaryString::fromString("abc"), true);
        $this->assertEquals("\x00\x03abc", $this->writer->getBuffer()->toString());
    }

    public function testWriteStringWithLengthDeprecated(): void
    {
        $this->writer->reset();
        $this->writer->writeStringWithLength(BinaryString::fromString("abc"));
        $this->assertEquals("\x03abc", $this->writer->getBuffer()->toString());

        $this->writer->reset();
        $this->writer->writeStringWithLength(BinaryString::fromString("abc"), true);
        $this->assertEquals("\x00\x03abc", $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithTerminatorNUL(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString("Hello");
        $this->writer->writeBytesWith($data, terminator: Terminator::NUL);
        $this->assertEquals("Hello\x00", $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithTerminatorGS(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString("World");
        $this->writer->writeBytesWith($data, terminator: Terminator::GS);
        $this->assertEquals("World\x1D", $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithCustomTerminator(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString("test");
        $customTerminator = BinaryString::fromString("\r\n");
        $this->writer->writeBytesWith($data, terminator: $customTerminator);
        $this->assertEquals("test\r\n", $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithTerminatorRejectsEmbeddedTerminator(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString("Hello\x00World");

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data contains terminator sequence');

        $this->writer->writeBytesWith($data, terminator: Terminator::NUL);
    }

    public function testWriteBytesWithOptionalTerminatorTriggersNotice(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('Hello');

        $notice = null;
        set_error_handler(function (int $errno, string $errstr) use (&$notice) {
            $notice = ['errno' => $errno, 'message' => $errstr];

            return true;
        }, E_USER_NOTICE);

        try {
            $this->writer->writeBytesWith($data, optional_terminator: Terminator::NUL);
        } finally {
            restore_error_handler();
        }

        $this->assertNotNull($notice, 'Expected notice not triggered');
        $this->assertSame(E_USER_NOTICE, $notice['errno']);
        $this->assertSame(
            'UNSTABLE API: optional_terminator has no effect when writing IN THIS VERSION; data will always be terminated IN THIS VERSION; it will probably change in future',
            $notice['message']
        );

        $this->assertEquals("Hello\x00", $this->writer->getBuffer()->toString());
    }

    public function testWriteStringWithTerminatorNUL(): void
    {
        $this->writer->reset();
        $string = BinaryString::fromString("Hello World");
        $this->writer->writeStringWith($string, terminator: Terminator::NUL);
        $this->assertEquals("Hello World\x00", $this->writer->getBuffer()->toString());
    }

    public function testWriteStringWithCustomTerminator(): void
    {
        $this->writer->reset();
        $string = BinaryString::fromString("Line 1");
        $newlineTerminator = BinaryString::fromString("\n");
        $this->writer->writeStringWith($string, terminator: $newlineTerminator);
        $this->assertEquals("Line 1\n", $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithParameterValidation(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString("test");

        // Test both parameters provided
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one of length, terminator, optional_terminator, or padding must be provided');

        $this->writer->writeBytesWith($data, IntType::UINT8, Terminator::NUL);
    }

    public function testWriteBytesWithNoParameters(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString("test");

        // Test no parameters provided
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one of length, terminator, optional_terminator, or padding must be provided');

        $this->writer->writeBytesWith($data);
    }

    public function testWriteStringWithInvalidUTF8AndTerminator(): void
    {
        $this->writer->reset();
        $invalidUTF8 = BinaryString::fromString("\xFF\xFE");

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String must be valid UTF-8');

        $this->writer->writeStringWith($invalidUTF8, terminator: Terminator::NUL);
    }

    public function testWriteBytesWithCRLFTerminator(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString("HTTP/1.1 200 OK");
        $this->writer->writeBytesWith($data, terminator: Terminator::CRLF);
        $this->assertEquals("HTTP/1.1 200 OK\x0D\x0A", $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithPaddingExactSize(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('ABCDE');

        $this->writer->writeBytesWith($data, padding: BinaryString::fromString("\x20"), padding_size: 5);

        $this->assertEquals('ABCDE', $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithPaddingShorterThanSize(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('Hi');

        $this->writer->writeBytesWith($data, padding: BinaryString::fromString("\x20"), padding_size: 5);

        $this->assertEquals("Hi   ", $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithPaddingDefaultPadByte(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('AB');

        $this->writer->writeBytesWith($data, padding_size: 4);

        $this->assertEquals("AB\x00\x00", $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithPaddingTooLongThrows(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('TooLong');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data too long for padding size');

        $this->writer->writeBytesWith($data, padding: BinaryString::fromString("\x20"), padding_size: 5);
    }

    public function testWriteBytesWithPaddingRejectsDataContainingPadByte(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('Hello World');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data contains padding byte');

        $this->writer->writeBytesWith($data, padding: BinaryString::fromString("\x20"), padding_size: 20);
    }

    public function testWriteBytesWithEmptyTerminatorThrows(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('Hello');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Terminator cannot be empty');

        $this->writer->writeBytesWith($data, terminator: BinaryString::fromString(''));
    }

    public function testWriteBytesWithPaddingRequiresSize(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('Data');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Padding size must be provided when padding is used');

        $this->writer->writeBytesWith($data, padding: BinaryString::fromString("\x20"));
    }

    public function testWriteBytesWithPaddingRejectsMultiBytePadding(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('Data');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Padding must be exactly one byte');

        $this->writer->writeBytesWith($data, padding: Terminator::CRLF, padding_size: 10);
    }

    public function testWriteBytesWithPaddingNegativeSizeThrows(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('AB');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Padding size cannot be negative');

        $this->writer->writeBytesWith($data, padding: BinaryString::fromString("\x20"), padding_size: -1);
    }

    public function testWriteBytesWithPaddingCannotCombineWithLength(): void
    {
        $this->writer->reset();
        $data = BinaryString::fromString('AB');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one of length, terminator, optional_terminator, or padding must be provided');

        $this->writer->writeBytesWith($data, length: IntType::UINT8, padding_size: 4);
    }

    public function testWriteStringWithPadding(): void
    {
        $this->writer->reset();
        $string = BinaryString::fromString('Hi');

        $this->writer->writeStringWith($string, padding: BinaryString::fromString("\x20"), padding_size: 4);

        $this->assertEquals("Hi  ", $this->writer->getBuffer()->toString());
    }

    public function testWriteStringWithCommonControlCharacters(): void
    {
        $this->writer->reset();

        // Test with Line Feed
        $this->writer->writeStringWith(BinaryString::fromString("Unix line"), terminator: Terminator::LF);
        $this->assertEquals("Unix line\x0A", $this->writer->getBuffer()->toString());

        // Test with Tab separator
        $this->writer->reset();
        $this->writer->writeBytesWith(BinaryString::fromString("field1"), terminator: Terminator::HT);
        $this->assertEquals("field1\x09", $this->writer->getBuffer()->toString());

        // Test with Record Separator
        $this->writer->reset();
        $this->writer->writeStringWith(BinaryString::fromString("record"), terminator: Terminator::RS);
        $this->assertEquals("record\x1E", $this->writer->getBuffer()->toString());
    }

    public function testWriteBytesWithProtocolSpecificTerminators(): void
    {
        $this->writer->reset();

        // STX/ETX for text boundaries
        $this->writer->writeBytesWith(BinaryString::fromString(""), terminator: Terminator::STX);
        $this->writer->writeBytesWith(BinaryString::fromString("Important Message"), terminator: Terminator::ETX);

        $expected = "\x02Important Message\x03";
        $this->assertEquals($expected, $this->writer->getBuffer()->toString());
    }
}
