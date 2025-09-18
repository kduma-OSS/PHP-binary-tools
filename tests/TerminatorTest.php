<?php declare(strict_types=1);

namespace KDuma\BinaryTools\Tests;

use KDuma\BinaryTools\BinaryString;
use KDuma\BinaryTools\Terminator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Terminator::class)]
class TerminatorTest extends TestCase
{
    public function testNulTerminator(): void
    {
        $terminator = Terminator::NUL;
        $bytes = $terminator->toBytes();

        $this->assertEquals("\x00", $bytes->toString());
        $this->assertEquals(1, $bytes->size());
    }

    public function testGsTerminator(): void
    {
        $terminator = Terminator::GS;
        $bytes = $terminator->toBytes();

        $this->assertEquals("\x1D", $bytes->toString());
        $this->assertEquals(1, $bytes->size());
    }

    public function testTerminatorEquality(): void
    {
        $nul1 = Terminator::NUL;
        $nul2 = Terminator::NUL;
        $gs = Terminator::GS;

        $this->assertEquals($nul1, $nul2);
        $this->assertNotEquals($nul1, $gs);
        $this->assertNotEquals($nul2, $gs);
    }

    public function testTerminatorBytes(): void
    {
        $nullBytes = BinaryString::fromString("\x00");
        $gsBytes = BinaryString::fromString("\x1D");

        $this->assertTrue(Terminator::NUL->toBytes()->equals($nullBytes));
        $this->assertTrue(Terminator::GS->toBytes()->equals($gsBytes));
        $this->assertFalse(Terminator::NUL->toBytes()->equals($gsBytes));
        $this->assertFalse(Terminator::GS->toBytes()->equals($nullBytes));
    }

    public function testCrlfTerminator(): void
    {
        $terminator = Terminator::CRLF;
        $bytes = $terminator->toBytes();

        $this->assertEquals("\x0D\x0A", $bytes->toString());
        $this->assertEquals(2, $bytes->size());
    }

    public function testCommonControlCharacters(): void
    {
        // Test some commonly used control characters
        $this->assertEquals("\x09", Terminator::HT->toBytes()->toString()); // Tab
        $this->assertEquals("\x0A", Terminator::LF->toBytes()->toString()); // Line Feed
        $this->assertEquals("\x0D", Terminator::CR->toBytes()->toString()); // Carriage Return
        $this->assertEquals("\x20", Terminator::SP->toBytes()->toString()); // Space
        $this->assertEquals("\x1C", Terminator::FS->toBytes()->toString()); // File Separator
        $this->assertEquals("\x1E", Terminator::RS->toBytes()->toString()); // Record Separator
        $this->assertEquals("\x1F", Terminator::US->toBytes()->toString()); // Unit Separator
    }

    public function testAllASCIIControlCharacters(): void
    {
        // Test that all ASCII control characters 0x00-0x20 are covered
        $testCases = [
            [Terminator::NUL, "\x00"],
            [Terminator::SOH, "\x01"],
            [Terminator::STX, "\x02"],
            [Terminator::ETX, "\x03"],
            [Terminator::EOT, "\x04"],
            [Terminator::ENQ, "\x05"],
            [Terminator::ACK, "\x06"],
            [Terminator::BEL, "\x07"],
            [Terminator::BS, "\x08"],
            [Terminator::HT, "\x09"],
            [Terminator::LF, "\x0A"],
            [Terminator::VT, "\x0B"],
            [Terminator::FF, "\x0C"],
            [Terminator::CR, "\x0D"],
            [Terminator::SO, "\x0E"],
            [Terminator::SI, "\x0F"],
            [Terminator::DLE, "\x10"],
            [Terminator::DC1, "\x11"],
            [Terminator::DC2, "\x12"],
            [Terminator::DC3, "\x13"],
            [Terminator::DC4, "\x14"],
            [Terminator::NAK, "\x15"],
            [Terminator::SYN, "\x16"],
            [Terminator::ETB, "\x17"],
            [Terminator::CAN, "\x18"],
            [Terminator::EM, "\x19"],
            [Terminator::SUB, "\x1A"],
            [Terminator::ESC, "\x1B"],
            [Terminator::FS, "\x1C"],
            [Terminator::GS, "\x1D"],
            [Terminator::RS, "\x1E"],
            [Terminator::US, "\x1F"],
            [Terminator::SP, "\x20"],
            [Terminator::CRLF, "\x0D\x0A"],
        ];

        foreach ($testCases as [$terminator, $expectedBytes]) {
            $actualBytes = $terminator->toBytes()->toString();
            $this->assertEquals(
                $expectedBytes,
                $actualBytes,
                "Terminator {$terminator->name} should produce expected bytes"
            );
        }
    }

    public function testProtocolSpecificTerminators(): void
    {
        // Test terminators commonly used in protocols

        // XON/XOFF flow control
        $this->assertEquals("\x11", Terminator::DC1->toBytes()->toString()); // XON
        $this->assertEquals("\x13", Terminator::DC3->toBytes()->toString()); // XOFF

        // Text boundaries
        $this->assertEquals("\x02", Terminator::STX->toBytes()->toString()); // Start of Text
        $this->assertEquals("\x03", Terminator::ETX->toBytes()->toString()); // End of Text

        // Data structure separators
        $this->assertEquals("\x1C", Terminator::FS->toBytes()->toString());  // File Separator
        $this->assertEquals("\x1D", Terminator::GS->toBytes()->toString());  // Group Separator
        $this->assertEquals("\x1E", Terminator::RS->toBytes()->toString());  // Record Separator
        $this->assertEquals("\x1F", Terminator::US->toBytes()->toString());  // Unit Separator

        // Communication control
        $this->assertEquals("\x04", Terminator::EOT->toBytes()->toString()); // End of Transmission
        $this->assertEquals("\x06", Terminator::ACK->toBytes()->toString()); // Acknowledge
        $this->assertEquals("\x15", Terminator::NAK->toBytes()->toString()); // Negative Acknowledge
    }
}
