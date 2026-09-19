<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;

class WebAuthnChallengeTest extends TestCase
{
    #[DataProvider('validByteLengthProvider')]
    public function testItAcceptsAChallengeWithinTheAllowedByteLength(int $byteLength): void
    {
        $binary = random_bytes($byteLength);
        $challenge = new WebAuthnChallenge(self::base64urlEncode($binary));

        $this->assertSame($binary, $challenge->toBinary());
    }

    /** @return array<string, array{int}> */
    public static function validByteLengthProvider(): array
    {
        return [
            'minimum' => [WebAuthnChallenge::MIN_BYTE_LENGTH],
            'maximum' => [WebAuthnChallenge::MAX_BYTE_LENGTH],
        ];
    }

    #[DataProvider('invalidChallengeProvider')]
    public function testItRejectsAnInvalidChallenge(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WebAuthnChallenge($value);
    }

    /** @return array<string, array{string}> */
    public static function invalidChallengeProvider(): array
    {
        return [
            'shorter than minimum' => [self::base64urlEncode(
                str_repeat('a', WebAuthnChallenge::MIN_BYTE_LENGTH - 1),
            )],
            'longer than maximum' => [self::base64urlEncode(
                str_repeat('a', WebAuthnChallenge::MAX_BYTE_LENGTH + 1),
            )],
            'invalid base64url character' => [str_repeat('a', 42) . '+'],
        ];
    }

    private static function base64urlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
