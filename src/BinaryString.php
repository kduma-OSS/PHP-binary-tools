<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

readonly class BinaryString
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

    public function equals(BinaryString $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
