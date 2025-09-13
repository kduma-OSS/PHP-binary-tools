<?php declare(strict_types=1);

namespace KDuma\BinaryTools\Tests;

use KDuma\BinaryTools\BinaryString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BinaryString::class)]
class BinaryStringTest extends TestCase
{
    private BinaryString $binaryString;

    protected function setUp(): void
    {
        $this->binaryString = BinaryString::fromString("\x01\x02\x03\x04");

        parent::setUp();
    }

    public function testToHex()
    {
        $this->assertEquals("01020304", $this->binaryString->toHex());
    }

    public function testToBase64()
    {
        $this->assertEquals("AQIDBA==", $this->binaryString->toBase64());
    }

    public function testFromBase64()
    {
        $reconstructedString = BinaryString::fromBase64("AQIDBA==");

        $this->assertEquals($this->binaryString, $reconstructedString);
    }

    public function testSize()
    {
        $this->assertEquals(4, $this->binaryString->size());
    }

    public function testFromHex()
    {
        $reconstructedString = BinaryString::fromHex("01020304");

        $this->assertEquals($this->binaryString, $reconstructedString);
    }

    public function testToString()
    {
        $this->assertEquals("\x01\x02\x03\x04", $this->binaryString->toString());
    }

    public function testFromString()
    {
        $reconstructedString = BinaryString::fromString("\x01\x02\x03\x04");

        $this->assertEquals($this->binaryString, $reconstructedString);
    }

    public function testEquals()
    {
        $this->assertTrue($this->binaryString->equals(BinaryString::fromString("\x01\x02\x03\x04")));
        $this->assertFalse($this->binaryString->equals(BinaryString::fromString("\xFF\xFF\xFF\xFF")));
    }
}
