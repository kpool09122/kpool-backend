<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\WebAuthn;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyCredential;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;

class VerifiedPasskeyCredentialTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $credentialId = new WebAuthnCredentialId('Y3JlZGVudGlhbA');
        $credentialSource = new CredentialSource('{"type":"public-key"}');
        $signCount = 7;
        $backupEligible = true;
        $backupState = true;
        $transports = [];

        $subject = new VerifiedPasskeyCredential($credentialId, $credentialSource, $signCount, $backupEligible, $backupState, $transports);

        $this->assertSame($credentialId, $subject->credentialId);
        $this->assertSame($credentialSource, $subject->credentialSource);
        $this->assertSame($signCount, $subject->signCount);
        $this->assertSame($backupEligible, $subject->backupEligible);
        $this->assertSame($backupState, $subject->backupState);
        $this->assertSame($transports, $subject->transports);
    }
}
