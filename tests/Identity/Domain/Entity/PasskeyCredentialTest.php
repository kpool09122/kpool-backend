<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Exception\InvalidPasskeyBackupStateException;
use Source\Identity\Domain\Exception\PasskeyBackupEligibilityChangedException;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;

class PasskeyCredentialTest extends TestCase
{
    public function testItRepresentsSyncedPlatformAndSecurityKeyCredentials(): void
    {
        $passkeyUserIdentifier = new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174000');

        $synced = $this->credential($passkeyUserIdentifier, true, true, ['internal', 'hybrid']);
        $platform = $this->credential($passkeyUserIdentifier, false, false, ['internal']);
        $securityKey = $this->credential($passkeyUserIdentifier, false, false, ['usb', 'nfc']);

        $this->assertTrue($synced->backupEligible());
        $this->assertTrue($synced->backupState());
        $this->assertSame(['internal', 'hybrid'], $synced->transports());
        $this->assertFalse($platform->backupEligible());
        $this->assertSame(['internal'], $platform->transports());
        $this->assertSame(['usb', 'nfc'], $securityKey->transports());
        $this->assertSame($passkeyUserIdentifier, $securityKey->passkeyUserIdentifier());
    }

    public function testItRejectsBackedUpStateForNonBackupEligibleCredential(): void
    {
        $this->expectException(InvalidPasskeyBackupStateException::class);

        $this->credential(
            new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174000'),
            false,
            true,
            ['internal'],
        );
    }

    public function testAuthenticationUpdatesMutableStateAndAllowsNonIncreasingCounterForSyncedCredential(): void
    {
        $credential = $this->credential(
            new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174000'),
            true,
            false,
            ['internal', 'hybrid'],
        );
        $usedAt = new DateTimeImmutable('2026-09-19T12:00:00+00:00');

        $credential->recordAuthentication(new CredentialSource('{"counter":5}'), 5, true, true, $usedAt);

        $this->assertSame(5, $credential->signCount());
        $this->assertTrue($credential->backupState());
        $this->assertSame($usedAt, $credential->lastUsedAt());
        $this->assertSame('{"counter":5}', (string) $credential->credentialSource());
    }

    public function testAuthenticationRejectsBackupEligibilityChange(): void
    {
        $credential = $this->credential(
            new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174000'),
            true,
            false,
            ['internal'],
        );

        $this->expectException(PasskeyBackupEligibilityChangedException::class);
        $credential->recordAuthentication(
            new CredentialSource('{"counter":6}'),
            6,
            false,
            false,
            new DateTimeImmutable(),
        );
    }

    /** @param string[] $transports */
    private function credential(
        PasskeyUserIdentifier $passkeyUserIdentifier,
        bool $backupEligible,
        bool $backupState,
        array $transports,
    ): PasskeyCredential {
        return new PasskeyCredential(
            new PasskeyCredentialIdentifier('123e4567-e89b-72d3-a456-426614174001'),
            $passkeyUserIdentifier,
            new WebAuthnCredentialId('Y3JlZGVudGlhbC1pZA'),
            new CredentialSource('{"credential":"source"}'),
            5,
            $backupEligible,
            $backupState,
            $transports,
            new PasskeyDisplayName('MacBook Touch ID'),
            null,
        );
    }
}
