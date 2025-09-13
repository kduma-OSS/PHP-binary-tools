# Binary Tools

A PHP library for binary data manipulation and encoding/decoding operations. This library provides safe, efficient tools for working with binary data, including UTF-8 validation and secure string comparisons.

## Installation

```bash
composer require kduma/binary-tools
```

## Requirements

- PHP 8.4+
- `ext-mbstring` - For UTF-8 validation
- `ext-hash` - For secure string comparisons

## Features

- **Safe binary data manipulation** with bounds checking
- **UTF-8 string validation** for text data
- **Multiple encoding formats** (hex, base64)
- **Secure string comparison** using `hash_equals()`
- **Big-endian integer support** for network protocols
- **Position tracking** for streaming operations

## Core Classes

### BinaryString

Immutable wrapper for binary data with conversion and comparison methods.

### BinaryWriter

Stream-like writer for building binary data structures.

### BinaryReader

Stream-like reader for parsing binary data with position tracking.

## Usage Examples

### BinaryString

```php
use KDuma\BinaryTools\BinaryString;

// Create from different sources
$binary = BinaryString::fromString("\x48\x65\x6c\x6c\x6f");
$fromString = BinaryString::fromString("Hello");
$fromHex = BinaryString::fromHex("48656c6c6f");
$fromBase64 = BinaryString::fromBase64("SGVsbG8=");

// All represent "Hello"
echo $binary->toString(); // "Hello"

// Convert to different formats
echo $binary->toHex();    // "48656c6c6f"
echo $binary->toBase64(); // "SGVsbG8="
echo $binary->size();     // 5

// Secure comparison
$other = BinaryString::fromString("Hello");
if ($binary->equals($other)) {
    echo "Strings are equal";
}
```

### BinaryWriter

```php
use KDuma\BinaryTools\BinaryWriter;
use KDuma\BinaryTools\BinaryString;

$writer = new BinaryWriter();

// Write different data types
$writer->writeByte(0x42)                          // Single byte
       ->writeUint16BE(1234)                      // 16-bit big-endian integer
       ->writeBytes(BinaryString::fromHex("abcd")) // Raw bytes
       ->writeString(BinaryString::fromString("Hello")); // UTF-8 string

// Write strings with length prefixes
$text = BinaryString::fromString("Hello World");
$writer->writeStringWithLength($text);        // 8-bit length + string
$writer->writeStringWithLength($text, true);  // 16-bit length + string

// Get the result
$result = $writer->getBuffer();
echo $result->toHex(); // Complete binary data as hex
```

### BinaryReader

```php
use KDuma\BinaryTools\BinaryReader;
use KDuma\BinaryTools\BinaryString;

$data = BinaryString::fromHex("4204d2abcd48656c6c6f0548656c6c6f20576f726c64000b48656c6c6f20576f726c64");
$reader = new BinaryReader($data);

// Read different data types
$byte = $reader->readByte();        // 0x42
$uint16 = $reader->readUint16BE();  // 1234
$bytes = $reader->readBytes(2);     // Raw bytes
$string = $reader->readBytes(5);    // "Hello"

// Read strings with length prefixes
$stringWithLength = $reader->readStringWithLength();      // 8-bit length
$stringWithLength16 = $reader->readStringWithLength(true); // 16-bit length

// Position management
echo $reader->position;        // Current position
echo $reader->remaining_bytes; // Bytes left
echo $reader->has_more_data;   // Boolean

// Peek without advancing
$nextByte = $reader->peekByte();
$next3Bytes = $reader->peekBytes(3);

// Seek to specific position
$reader->seek(0);  // Go to start
$reader->skip(5);  // Skip 5 bytes
```

## Common Use Cases

### Protocol Implementation

```php
// Writing a simple protocol message
$writer = new BinaryWriter();
$message = BinaryString::fromString("Hello, Protocol!");

$writer->writeByte(0x01)                    // Message type
       ->writeUint16BE(time() & 0xFFFF)     // Timestamp (16-bit)
       ->writeStringWithLength($message);    // Payload

$packet = $writer->getBuffer();

// Reading the protocol message
$reader = new BinaryReader($packet);
$messageType = $reader->readByte();
$timestamp = $reader->readUint16BE();
$payload = $reader->readStringWithLength();

echo "Type: {$messageType}, Time: {$timestamp}, Message: {$payload->toString()}";
```

### File Header Parsing

```php
// Parse a file with magic bytes and metadata
$fileData = BinaryString::fromHex("4d5a90000300000004000000ffff0000");
$reader = new BinaryReader($fileData);

$magic = $reader->readBytes(2)->toString();  // "MZ"
if ($magic === "MZ") {
    $bytesOnLastPage = $reader->readUint16BE();
    $pagesInFile = $reader->readUint16BE();
    // ... continue parsing
}
```

### Data Serialization

```php
// Serialize complex data
$writer = new BinaryWriter();

$users = [
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 2, 'name' => 'Bob'],
];

$writer->writeByte(count($users)); // User count

foreach ($users as $user) {
    $writer->writeUint16BE($user['id']);
    $writer->writeStringWithLength(BinaryString::fromString($user['name']));
}

$serialized = $writer->getBuffer();

// Deserialize
$reader = new BinaryReader($serialized);
$userCount = $reader->readByte();

for ($i = 0; $i < $userCount; $i++) {
    $userId = $reader->readUint16BE();
    $userName = $reader->readStringWithLength()->toString();
    echo "User {$userId}: {$userName}\n";
}
```

## API Reference

### BinaryString

| Method | Description |
|--------|-------------|
| `toString(): string` | Get raw binary data |
| `toHex(): string` | Convert to hexadecimal string |
| `toBase64(): string` | Convert to base64 string |
| `size(): int` | Get byte length |
| `equals(BinaryString $other): bool` | Secure comparison |
| `fromString(string $value): static` | Create from string |
| `fromHex(string $hex): static` | Create from hex string |
| `fromBase64(string $base64): static` | Create from base64 |

### BinaryWriter

| Method | Description |
|--------|-------------|
| `getBuffer(): BinaryString` | Get written data |
| `getLength(): int` | Get buffer length |
| `reset(): void` | Clear buffer |
| `writeByte(int $byte): self` | Write single byte (0-255) |
| `writeBytes(BinaryString $bytes): self` | Write binary data |
| `writeUint16BE(int $value): self` | Write 16-bit big-endian integer |
| `writeString(BinaryString $string): self` | Write UTF-8 string |
| `writeStringWithLength(BinaryString $string, bool $use16BitLength = false): self` | Write string with length prefix |
| `writeBytesWithLength(BinaryString $bytes, bool $use16BitLength = false): self` | Write bytes with length prefix |

### BinaryReader

| Property/Method | Description |
|-----------------|-------------|
| `$position` | Current read position |
| `$length` | Total data length |
| `$remaining_bytes` | Bytes remaining |
| `$has_more_data` | Whether more data available |
| `$data` | Get original data as BinaryString |
| `$remaining_data` | Get remaining data |
| `readByte(): int` | Read single byte |
| `readBytes(int $count): BinaryString` | Read N bytes |
| `readUint16BE(): int` | Read 16-bit big-endian integer |
| `readString(int $length): BinaryString` | Read UTF-8 string of specific length |
| `readStringWithLength(bool $use16BitLength = false): BinaryString` | Read string with length prefix |
| `readBytesWithLength(bool $use16BitLength = false): BinaryString` | Read bytes with length prefix |
| `peekByte(): int` | Peek next byte without advancing |
| `peekBytes(int $count): BinaryString` | Peek N bytes without advancing |
| `seek(int $position): void` | Seek to position |
| `skip(int $count): void` | Skip N bytes |

## Error Handling

The library throws appropriate exceptions for error conditions:

- `InvalidArgumentException` - Invalid parameters (e.g., byte values > 255)
- `RuntimeException` - Runtime errors (e.g., reading past end of data, invalid UTF-8)

```php
try {
    $reader = new BinaryReader(BinaryString::fromHex("41"));
    $reader->readBytes(5); // Trying to read more than available
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage();
}
```

## License

This library is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).