<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\WebAuthn;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyAuthentication;
use Source\Identity\Domain\ValueObject\CredentialSource;

class VerifiedPasskeyAuthenticationTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $credentialSource = new CredentialSource('{"type":"public-key"}');
        $signCount = 7;
        $backupEligible = true;
        $backupState = true;

        $subject = new VerifiedPasskeyAuthentication($credentialSource, $signCount, $backupEligible, $backupState);

        $this->assertSame($credentialSource, $subject->credentialSource);
        $this->assertSame($signCount, $subject->signCount);
        $this->assertSame($backupEligible, $subject->backupEligible);
        $this->assertSame($backupState, $subject->backupState);
    }
}
