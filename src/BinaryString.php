<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

final readonly class BinaryString
{
    protected function __construct(public string $value)
    {
    }

    /**
     * Returns the raw binary value as a PHP string.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Serialises the binary value into an ASCII hexadecimal string.
     */
    public function toHex(): string
    {
        return bin2hex($this->value);
    }

    /**
     * Serialises the binary value using Base64 encoding.
     */
    public function toBase64(): string
    {
        return base64_encode($this->value);
    }

    /**
     * Returns a Base32-encoded string representation of the binary value.
     *
     * @param string $alphabet Alphabet to use when encoding.
     */
    public function toBase32(string $alphabet = Base32::DEFAULT_ALPHABET): string
    {
        return Base32::toBase32($this->value, $alphabet);
    }

    /**
     * Returns the number of bytes contained in the value.
     */
    public function size(): int
    {
        return strlen($this->value);
    }

    /**
     * Creates a BinaryString from an existing PHP string without validation.
     *
     * @param string $value Raw binary data.
     */
    public static function fromString(string $value): static
    {
        return new static($value);
    }

    /**
     * Creates a BinaryString from a hexadecimal dump.
     *
     * @param string $hex Hexadecimal representation of the data.
     */
    public static function fromHex(string $hex): static
    {
        return new static(hex2bin($hex));
    }

    /**
     * Creates a BinaryString from a Base64-encoded payload.
     *
     * @param string $base64 Base64 representation of the data.
     */
    public static function fromBase64(string $base64): static
    {
        return new static(base64_decode($base64, true));
    }

    /**
     * Decodes a Base32-encoded string to a BinaryString instance using the specified alphabet.
     *
     * @param string $base32 Base32 payload to decode.
     * @param string $alphabet Alphabet that was used during encoding.
     */
    public static function fromBase32(string $base32, string $alphabet = Base32::DEFAULT_ALPHABET): static
    {
        return new static(Base32::fromBase32($base32, $alphabet));
    }

    /**
     * Performs a timing-safe comparison with another BinaryString.
     *
     * @param BinaryString $other Value to compare against.
     */
    public function equals(BinaryString $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    /**
     * Determines whether the provided binary fragment appears in the value.
     *
     * @param BinaryString $needle Fragment to look for.
     */
    public function contains(BinaryString $needle): bool
    {
        if ($needle->size() === 0) {
            return true;
        }

        return str_contains($this->value, $needle->value);
    }
}
