<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Application\UseCase\Command\RequestDelegation\RequestDelegationOutput;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Application\Exception\OutputNotInitializedException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

class RequestDelegationOutputTest extends TestCase
{
    public function testSerializesSuppliedEntity(): void
    {
        $output = new RequestDelegationOutput();
        $entity = new Delegation(new DelegationIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AffiliationIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), DelegationStatus::PENDING, DelegationDirection::FROM_AGENCY, new DateTimeImmutable('2026-10-03T01:02:03+00:00'), new DateTimeImmutable('2026-10-03T01:02:03+00:00'), new DateTimeImmutable('2026-10-03T01:02:03+00:00'));
        $output->setDelegation($entity);
        $this->assertSame([
            'delegationIdentifier' => (string) new DelegationIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            'affiliationIdentifier' => (string) new AffiliationIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            'delegateAccountIdentifier' => (string) new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            'delegatorAccountIdentifier' => (string) new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            'requestedByAccountIdentifier' => (string) new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            'status' => DelegationStatus::PENDING->value,
            'direction' => DelegationDirection::FROM_AGENCY->value,
            'requestedAt' => '2026-10-03T01:02:03+00:00',
            'approvedAt' => '2026-10-03T01:02:03+00:00',
            'rejectedAt' => '2026-10-03T01:02:03+00:00',
        ], $output->toArray());
    }

    public function testRejectsUninitializedOutput(): void
    {
        $this->expectException(OutputNotInitializedException::class);
        (new RequestDelegationOutput())->toArray();
    }
}
