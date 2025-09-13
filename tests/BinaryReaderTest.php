<?php declare(strict_types=1);

namespace KDuma\BinaryTools\Tests;

use KDuma\BinaryTools\BinaryReader;
use KDuma\BinaryTools\BinaryString;
use PHPUnit\Framework\Attributes\CoversClass;
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

    public function testReadUint16BE()
    {
        $this->reader = new BinaryReader(BinaryString::fromString("\x04\xd2"));
        $this->assertEquals(1234, $this->reader->readUint16BE());
        $this->assertEquals(2, $this->reader->position);
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

    public function testGetRemainingBytes()
    {
        $this->reader->seek(0);
        $this->assertEquals(4, $this->reader->remaining_bytes);

        $this->reader->seek(2);
        $this->assertEquals(2, $this->reader->remaining_bytes);

        $this->reader->seek(4);
        $this->assertEquals(0, $this->reader->remaining_bytes);
    }
}
