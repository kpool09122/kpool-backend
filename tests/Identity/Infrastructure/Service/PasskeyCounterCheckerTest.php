<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use PHPUnit\Framework\TestCase;
use Source\Identity\Infrastructure\Service\PasskeyCounterChecker;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\Exception\CounterException;
use Webauthn\TrustPath\EmptyTrustPath;

class PasskeyCounterCheckerTest extends TestCase
{
    public function testSingleDeviceCredentialRejectsNonIncreasingCounterIncludingResetToZero(): void
    {
        $record = $this->record(counter: 5, backupEligible: false);
        $this->expectException(CounterException::class);

        (new PasskeyCounterChecker())->check($record, 0);
    }

    public function testSingleDeviceCredentialAcceptsIncreasingCounter(): void
    {
        (new PasskeyCounterChecker())->check($this->record(counter: 5, backupEligible: false), 6);

        $this->addToAssertionCount(1);
    }

    public function testMultiDeviceCredentialAllowsNonIncreasingCounter(): void
    {
        (new PasskeyCounterChecker())->check($this->record(counter: 5, backupEligible: true), 0);

        $this->addToAssertionCount(1);
    }

    private function record(int $counter, bool $backupEligible): CredentialRecord
    {
        return new CredentialRecord(
            'credential-id',
            'public-key',
            [],
            'none',
            EmptyTrustPath::create(),
            Uuid::v4(),
            'public-key',
            'user-handle',
            $counter,
            backupEligible: $backupEligible,
        );
    }
}
