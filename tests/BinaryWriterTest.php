<?php declare(strict_types=1);

namespace KDuma\BinaryTools\Tests;

use KDuma\BinaryTools\BinaryString;
use KDuma\BinaryTools\BinaryWriter;
use PHPUnit\Framework\Attributes\CoversClass;
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
            $this->assertEquals('Uint16 value must be between 0 and 65535', $exception->getMessage());
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
            $this->assertEquals('String too long for 8-bit length field', $exception->getMessage());
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
            $this->assertEquals('String too long for 16-bit length field', $exception->getMessage());
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
}
