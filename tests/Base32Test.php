<?php declare(strict_types=1);

namespace KDuma\BinaryTools\Tests;

use KDuma\BinaryTools\Base32;
use KDuma\BinaryTools\BinaryString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Base32::class)]
class Base32Test extends TestCase
{
    public function testEmpty(): void
    {
        $this->assertSame('', Base32::toBase32(''));
        $this->assertSame('', Base32::fromBase32(''));

        $binaryString = BinaryString::fromString('');
        $this->assertSame('', $binaryString->toBase32());
        $this->assertTrue($binaryString->equals(BinaryString::fromBase32('')));
    }

    /**
     * RFC 4648 test vectors (uppercase, no padding) adapted to unpadded form.
     *
     * @return array<string, array{plain: string, base32: string}>
     */
    public static function vectors(): array
    {
        return [
            'f' => ['plain' => 'f',       'base32' => 'MY'],
            'fo' => ['plain' => 'fo',      'base32' => 'MZXQ'],
            'foo' => ['plain' => 'foo',     'base32' => 'MZXW6'],
            'foob' => ['plain' => 'foob',    'base32' => 'MZXW6YQ'],
            'fooba' => ['plain' => 'fooba',   'base32' => 'MZXW6YTB'],
            'foobar' => ['plain' => 'foobar',  'base32' => 'MZXW6YTBOI'],
            'A' => ['plain' => 'A',       'base32' => 'IE'],
            'AB' => ['plain' => 'AB',      'base32' => 'IFBA'],
            'ABC' => ['plain' => 'ABC',     'base32' => 'IFBEG'],
        ];
    }

    #[DataProvider('vectors')]
    public function testToBase32MatchesKnownVectors(string $plain, string $base32): void
    {
        $this->assertSame($base32, Base32::toBase32($plain));
        $this->assertSame($base32, BinaryString::fromString($plain)->toBase32());
    }

    #[DataProvider('vectors')]
    public function testFromBase32MatchesKnownVectors(string $plain, string $base32): void
    {
        $this->assertSame($plain, Base32::fromBase32($base32));
        $this->assertTrue(BinaryString::fromString($plain)->equals(BinaryString::fromBase32($base32)));
    }

    public function testRoundTripRandomBinary(): void
    {
        $lengths = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 16, 31, 32, 33, 64, 100, 256, 1024];

        foreach ($lengths as $length) {
            $binary = ($length === 0) ? '' : random_bytes($length);
            $encoded = Base32::toBase32($binary);
            $decoded = Base32::fromBase32($encoded);

            $this->assertSame($binary, $decoded, "Failed round-trip at length {$length}");
        }
    }

    public function testDecodeThenEncodeIsIdempotent(): void
    {
        $original = 'MZXW6YTBOI'; // "foobar"
        $decoded = Base32::fromBase32($original);
        $reEncoded = Base32::toBase32($decoded);

        $this->assertSame($original, $reEncoded);
    }

    public function testLowercaseInputDoesNotDecodeToExpectedValue(): void
    {
        $lower = 'mzxw6ytboi';
        $decodedLower = Base32::fromBase32($lower);

        $this->assertNotSame('foobar', $decodedLower);
    }
}
