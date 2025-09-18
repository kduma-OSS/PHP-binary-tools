<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

final readonly class BinaryString
{
    protected function __construct(public string $value)
    {
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toHex(): string
    {
        return bin2hex($this->value);
    }

    public function toBase64(): string
    {
        return base64_encode($this->value);
    }

    /**
     * Returns a Base32-encoded string representation of the binary value.
     *
     * @param string $alphabet The alphabet to use for Base32 encoding.
     * @return string The Base32-encoded string.
     */
    public function toBase32(string $alphabet = Base32::DEFAULT_ALPHABET): string
    {
        return Base32::toBase32($this->value, $alphabet);
    }

    public function size(): int
    {
        return strlen($this->value);
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public static function fromHex(string $hex): static
    {
        return new static(hex2bin($hex));
    }

    public static function fromBase64(string $base64): static
    {
        return new static(base64_decode($base64, true));
    }

    /**
     * Decodes a Base32-encoded string to a BinaryString instance.
     *
     * @param string $base32 The Base32-encoded string to decode.
     * @param string $alphabet The alphabet used for Base32 encoding. Defaults to Base32::DEFAULT_ALPHABET.
     * @return static A new BinaryString instance containing the decoded binary data.
     */
    public static function fromBase32(string $base32, string $alphabet = Base32::DEFAULT_ALPHABET): static
    {
        return new static(Base32::fromBase32($base32, $alphabet));
    }

    public function equals(BinaryString $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    public function contains(BinaryString $needle): bool
    {
        if ($needle->size() === 0) {
            return true;
        }

        return str_contains($this->value, $needle->value);
    }
}
