<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

enum Terminator
{
    // ASCII control characters 0x00-0x1F

    /** Null character (0x00) - Commonly used for C-style string termination */
    case NUL;

    /** Start of Heading (0x01) - Indicates the start of a header block */
    case SOH;

    /** Start of Text (0x02) - Marks the beginning of text data */
    case STX;

    /** End of Text (0x03) - Marks the end of text data */
    case ETX;

    /** End of Transmission (0x04) - Indicates end of data transmission */
    case EOT;

    /** Enquiry (0x05) - Request for response or status */
    case ENQ;

    /** Acknowledge (0x06) - Positive acknowledgment signal */
    case ACK;

    /** Bell (0x07) - Audio alert or notification signal */
    case BEL;

    /** Backspace (0x08) - Move cursor back one position */
    case BS;

    /** Horizontal Tab (0x09) - Move to next tab stop */
    case HT;

    /** Line Feed (0x0A) - Move to next line (Unix line ending) */
    case LF;

    /** Vertical Tab (0x0B) - Move to next vertical tab position */
    case VT;

    /** Form Feed (0x0C) - Start new page or clear screen */
    case FF;

    /** Carriage Return (0x0D) - Return to start of line (classic Mac line ending) */
    case CR;

    /** Shift Out (0x0E) - Switch to alternate character set */
    case SO;

    /** Shift In (0x0F) - Switch back to standard character set */
    case SI;

    /** Data Link Escape (0x10) - Escape sequence for data link protocols */
    case DLE;

    /** Device Control 1 (0x11) - Also known as XON for flow control */
    case DC1;

    /** Device Control 2 (0x12) - General device control */
    case DC2;

    /** Device Control 3 (0x13) - Also known as XOFF for flow control */
    case DC3;

    /** Device Control 4 (0x14) - General device control */
    case DC4;

    /** Negative Acknowledge (0x15) - Error or rejection signal */
    case NAK;

    /** Synchronous Idle (0x16) - Synchronization in data streams */
    case SYN;

    /** End of Transmission Block (0x17) - End of data block marker */
    case ETB;

    /** Cancel (0x18) - Cancel current operation */
    case CAN;

    /** End of Medium (0x19) - End of storage medium */
    case EM;

    /** Substitute (0x1A) - Replacement for invalid character */
    case SUB;

    /** Escape (0x1B) - Start of escape sequence */
    case ESC;

    /** File Separator (0x1C) - Delimiter between files */
    case FS;

    /** Group Separator (0x1D) - Delimiter between groups of data */
    case GS;

    /** Record Separator (0x1E) - Delimiter between records */
    case RS;

    /** Unit Separator (0x1F) - Delimiter between units of data */
    case US;

    /** Space (0x20) - Standard whitespace character */
    case SP;

    // Common multi-character sequences

    /** Carriage Return + Line Feed (0x0D 0x0A) - Windows line ending */
    case CRLF;

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
