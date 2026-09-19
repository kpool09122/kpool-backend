<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;

class WebAuthnCredentialIdTest extends TestCase
{
    public function testItRestoresTheBinaryCredentialId(): void
    {
        $binary = random_bytes(32);
        $credentialId = WebAuthnCredentialId::fromBinary($binary);

        $this->assertSame($binary, $credentialId->toBinary());
    }

    public function testItAcceptsTheMaximumByteLength(): void
    {
        $binary = str_repeat('a', WebAuthnCredentialId::MAX_BYTE_LENGTH);

        $this->assertSame($binary, WebAuthnCredentialId::fromBinary($binary)->toBinary());
    }

    #[DataProvider('invalidCredentialIdProvider')]
    public function testItRejectsAnInvalidCredentialId(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WebAuthnCredentialId($value);
    }

    /** @return array<string, array{string}> */
    public static function invalidCredentialIdProvider(): array
    {
        return [
            'empty' => [''],
            'longer than maximum' => [self::base64urlEncode(
                str_repeat('a', WebAuthnCredentialId::MAX_BYTE_LENGTH + 1),
            )],
            'invalid base64url character' => [self::base64urlEncode('credential-id') . '+'],
            'invalid base64url length' => ['a'],
        ];
    }

    private static function base64urlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
