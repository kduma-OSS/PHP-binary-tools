<?php declare(strict_types=1);

namespace KDuma\BinaryTools;

use InvalidArgumentException;

/**
 * Base32 encoder/decoder using a configurable alphabet (default RFC 4648 without padding).
 */
final class Base32
{
    public const DEFAULT_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** @var array<string, array<string, int>> */
    private static array $decodeMaps = [];

    /** @var array<string, bool> */
    private static array $validatedAlphabets = [];

    public static function toBase32(string $binary, string $alphabet = self::DEFAULT_ALPHABET): string
    {
        self::ensureValidAlphabet($alphabet);

        if ($binary === '') {
            return '';
        }

        $result = '';
        $buffer = 0;
        $bitsLeft = 0;
        $length = strlen($binary);

        for ($i = 0; $i < $length; $i++) {
            $buffer = ($buffer << 8) | ord($binary[$i]);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $index = ($buffer >> $bitsLeft) & 0x1F;
                $result .= $alphabet[$index];
            }
        }

        if ($bitsLeft > 0) {
            $index = ($buffer << (5 - $bitsLeft)) & 0x1F;
            $result .= $alphabet[$index];
        }

        return $result;
    }

    public static function fromBase32(string $base32, string $alphabet = self::DEFAULT_ALPHABET): string
    {
        if ($base32 === '') {
            return '';
        }

        $map = self::decodeMap($alphabet);

        $buffer = 0;
        $bitsLeft = 0;
        $output = '';
        $length = strlen($base32);

        for ($i = 0; $i < $length; $i++) {
            $char = $base32[$i];

            if ($char === '=') {
                break; // padding reached (RFC 4648)
            }

            if (!isset($map[$char])) {
                return '';
            }

            $buffer = ($buffer << 5) | $map[$char];
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }

    /**
     * @return array<string, int>
     */
    private static function decodeMap(string $alphabet): array
    {
        if (!isset(self::$decodeMaps[$alphabet])) {
            self::ensureValidAlphabet($alphabet);

            $map = [];
            for ($i = 0; $i < 32; $i++) {
                $char = $alphabet[$i];
                $map[$char] = $i;
            }

            self::$decodeMaps[$alphabet] = $map;
        }

        return self::$decodeMaps[$alphabet];
    }

    private static function ensureValidAlphabet(string $alphabet): void
    {
        if (isset(self::$validatedAlphabets[$alphabet])) {
            return;
        }

        if (strlen($alphabet) !== 32) {
            throw new InvalidArgumentException('Base32 alphabet must contain exactly 32 characters.');
        }

        $characters = [];
        for ($i = 0; $i < 32; $i++) {
            $char = $alphabet[$i];
            if (isset($characters[$char])) {
                throw new InvalidArgumentException('Base32 alphabet must contain unique characters.');
            }
            $characters[$char] = true;
        }

        self::$validatedAlphabets[$alphabet] = true;
    }
}
