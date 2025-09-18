<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

enum Terminator
{
    // ASCII control characters 0x00-0x1F
    case NUL;    // 0x00 - Null character
    case SOH;    // 0x01 - Start of Heading
    case STX;    // 0x02 - Start of Text
    case ETX;    // 0x03 - End of Text
    case EOT;    // 0x04 - End of Transmission
    case ENQ;    // 0x05 - Enquiry
    case ACK;    // 0x06 - Acknowledge
    case BEL;    // 0x07 - Bell
    case BS;     // 0x08 - Backspace
    case HT;     // 0x09 - Horizontal Tab
    case LF;     // 0x0A - Line Feed
    case VT;     // 0x0B - Vertical Tab
    case FF;     // 0x0C - Form Feed
    case CR;     // 0x0D - Carriage Return
    case SO;     // 0x0E - Shift Out
    case SI;     // 0x0F - Shift In
    case DLE;    // 0x10 - Data Link Escape
    case DC1;    // 0x11 - Device Control 1 (XON)
    case DC2;    // 0x12 - Device Control 2
    case DC3;    // 0x13 - Device Control 3 (XOFF)
    case DC4;    // 0x14 - Device Control 4
    case NAK;    // 0x15 - Negative Acknowledge
    case SYN;    // 0x16 - Synchronous Idle
    case ETB;    // 0x17 - End of Transmission Block
    case CAN;    // 0x18 - Cancel
    case EM;     // 0x19 - End of Medium
    case SUB;    // 0x1A - Substitute
    case ESC;    // 0x1B - Escape
    case FS;     // 0x1C - File Separator
    case GS;     // 0x1D - Group Separator
    case RS;     // 0x1E - Record Separator
    case US;     // 0x1F - Unit Separator
    case SP;     // 0x20 - Space

    // Common multi-character sequences
    case CRLF;   // 0x0D 0x0A - Carriage Return + Line Feed

    public function toBytes(): BinaryString
    {
        return match ($this) {
            self::NUL => BinaryString::fromString("\x00"),
            self::SOH => BinaryString::fromString("\x01"),
            self::STX => BinaryString::fromString("\x02"),
            self::ETX => BinaryString::fromString("\x03"),
            self::EOT => BinaryString::fromString("\x04"),
            self::ENQ => BinaryString::fromString("\x05"),
            self::ACK => BinaryString::fromString("\x06"),
            self::BEL => BinaryString::fromString("\x07"),
            self::BS => BinaryString::fromString("\x08"),
            self::HT => BinaryString::fromString("\x09"),
            self::LF => BinaryString::fromString("\x0A"),
            self::VT => BinaryString::fromString("\x0B"),
            self::FF => BinaryString::fromString("\x0C"),
            self::CR => BinaryString::fromString("\x0D"),
            self::SO => BinaryString::fromString("\x0E"),
            self::SI => BinaryString::fromString("\x0F"),
            self::DLE => BinaryString::fromString("\x10"),
            self::DC1 => BinaryString::fromString("\x11"),
            self::DC2 => BinaryString::fromString("\x12"),
            self::DC3 => BinaryString::fromString("\x13"),
            self::DC4 => BinaryString::fromString("\x14"),
            self::NAK => BinaryString::fromString("\x15"),
            self::SYN => BinaryString::fromString("\x16"),
            self::ETB => BinaryString::fromString("\x17"),
            self::CAN => BinaryString::fromString("\x18"),
            self::EM => BinaryString::fromString("\x19"),
            self::SUB => BinaryString::fromString("\x1A"),
            self::ESC => BinaryString::fromString("\x1B"),
            self::FS => BinaryString::fromString("\x1C"),
            self::GS => BinaryString::fromString("\x1D"),
            self::RS => BinaryString::fromString("\x1E"),
            self::US => BinaryString::fromString("\x1F"),
            self::SP => BinaryString::fromString("\x20"),
            self::CRLF => BinaryString::fromString("\x0D\x0A"),
        };
    }
}
