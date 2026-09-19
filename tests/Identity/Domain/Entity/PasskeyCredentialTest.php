<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Exception\InvalidPasskeyException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class PasskeyCredentialTest extends TestCase
{
    public function testCredentialSupportsSynchronizedPasskeyAndAuthenticationUpdate(): void
    {
        $credential = $this->credential(backupEligible: true, backupState: false);
        $usedAt = new DateTimeImmutable();

        $credential->recordAuthentication('updated-source', 0, true, true, $usedAt);

        $this->assertSame('updated-source', $credential->credentialSource());
        $this->assertSame(0, $credential->signCount());
        $this->assertTrue($credential->backupState());
        $this->assertSame($usedAt, $credential->lastUsedAt());
    }

    public function testSingleDeviceCredentialCannotHaveBackupState(): void
    {
        $this->expectException(InvalidPasskeyException::class);

        $this->credential(backupEligible: false, backupState: true);
    }

    public function testBackupEligibilityCannotChangeAfterRegistration(): void
    {
        $credential = $this->credential(backupEligible: true, backupState: true);
        $this->expectException(InvalidPasskeyException::class);

        $credential->recordAuthentication('source', 1, false, false, new DateTimeImmutable());
    }

    public function testDisplayNameCanBeChanged(): void
    {
        $credential = $this->credential();
        $credential->rename('Security key');

        $this->assertSame('Security key', $credential->displayName());
    }

    private function credential(bool $backupEligible = false, bool $backupState = false): PasskeyCredential
    {
        return new PasskeyCredential(
            StrTestHelper::generateUuid(),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            'binary-credential-id',
            '{"credential":"source"}',
            0,
            $backupEligible,
            $backupState,
            ['internal', 'usb'],
            'My passkey',
        );
    }
}
