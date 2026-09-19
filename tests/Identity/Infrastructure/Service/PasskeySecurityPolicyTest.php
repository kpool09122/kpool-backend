<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use PHPUnit\Framework\TestCase;
use Source\Identity\Infrastructure\Service\PasskeyCounterChecker;
use Source\Identity\Infrastructure\Service\WebAuthnChallengeGenerator;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\TrustPath\EmptyTrustPath;

class PasskeySecurityPolicyTest extends TestCase
{
    public function testChallengeGeneratorCreatesIndependent256BitChallenges(): void
    {
        $generator = new WebAuthnChallengeGenerator();
        $first = $generator->generate();
        $second = $generator->generate();

        $this->assertNotSame((string) $first, (string) $second);
        $this->assertSame(32, strlen($first->toBinary()));
        $this->assertSame(32, strlen($second->toBinary()));
    }

    public function testCounterPolicyAllowsNonIncreasingCounterForBackupEligibleCredential(): void
    {
        $checker = new PasskeyCounterChecker();

        $checker->check($this->record(10, true, true), 10);
        $this->addToAssertionCount(1);
    }

    public function testCounterPolicyRejectsNonIncreasingCounterForDeviceBoundCredential(): void
    {
        $checker = new PasskeyCounterChecker();

        $this->expectException(AuthenticatorResponseVerificationException::class);
        $checker->check($this->record(10, false, false), 10);
    }

    public function testCounterPolicyAllowsAuthenticatorsThatDoNotSupportCounters(): void
    {
        $checker = new PasskeyCounterChecker();

        $checker->check($this->record(0, false, false), 0);
        $this->addToAssertionCount(1);
    }

    private function record(int $counter, bool $backupEligible, bool $backupStatus): CredentialRecord
    {
        return CredentialRecord::create(
            'credential-id',
            'public-key',
            ['internal'],
            'none',
            EmptyTrustPath::create(),
            Uuid::v7(),
            'credential-public-key',
            'user-handle',
            $counter,
            backupEligible: $backupEligible,
            backupStatus: $backupStatus,
            uvInitialized: true,
        );
    }
}
