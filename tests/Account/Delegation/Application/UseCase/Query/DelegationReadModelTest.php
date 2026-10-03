<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Application\UseCase\Query\DelegationReadModel;

class DelegationReadModelTest extends TestCase
{
    public function testToArrayReturnsDelegationSummary(): void
    {
        $readModel = new DelegationReadModel(
            delegationIdentifier: 'delegation-id',
            affiliationIdentifier: 'affiliation-id',
            delegateAccountIdentifier: 'delegate-account-id',
            delegatorAccountIdentifier: 'delegator-account-id',
            requestedByAccountIdentifier: 'requested-by-account-id',
            delegateAccount: ['accountIdentifier' => 'delegate-account-id', 'name' => 'Delegate Account', 'email' => 'delegate@example.test'],
            delegatorAccount: ['accountIdentifier' => 'delegator-account-id', 'name' => 'Delegator Account', 'email' => 'delegator@example.test'],
            requestedByAccount: ['accountIdentifier' => 'requested-by-account-id', 'name' => 'Requester Account', 'email' => 'requester@example.test'],
            status: 'approved',
            direction: 'from_agency',
            requestedAt: '2026-09-10T10:00:00+00:00',
            approvedAt: '2026-09-11T10:00:00+00:00',
            rejectedAt: null,
        );

        $this->assertSame([
            'delegationIdentifier' => 'delegation-id',
            'affiliationIdentifier' => 'affiliation-id',
            'delegateAccountIdentifier' => 'delegate-account-id',
            'delegatorAccountIdentifier' => 'delegator-account-id',
            'requestedByAccountIdentifier' => 'requested-by-account-id',
            'delegateAccount' => ['accountIdentifier' => 'delegate-account-id', 'name' => 'Delegate Account', 'email' => 'delegate@example.test'],
            'delegatorAccount' => ['accountIdentifier' => 'delegator-account-id', 'name' => 'Delegator Account', 'email' => 'delegator@example.test'],
            'requestedByAccount' => ['accountIdentifier' => 'requested-by-account-id', 'name' => 'Requester Account', 'email' => 'requester@example.test'],
            'status' => 'approved',
            'direction' => 'from_agency',
            'requestedAt' => '2026-09-10T10:00:00+00:00',
            'approvedAt' => '2026-09-11T10:00:00+00:00',
            'rejectedAt' => null,
        ], $readModel->toArray());
    }

    public function testToArrayReturnsRejectedAt(): void
    {
        $readModel = new DelegationReadModel(
            delegationIdentifier: 'delegation-id',
            affiliationIdentifier: 'affiliation-id',
            delegateAccountIdentifier: 'delegate-account-id',
            delegatorAccountIdentifier: 'delegator-account-id',
            requestedByAccountIdentifier: 'requested-by-account-id',
            delegateAccount: ['accountIdentifier' => 'delegate-account-id', 'name' => 'Delegate Account', 'email' => 'delegate@example.test'],
            delegatorAccount: ['accountIdentifier' => 'delegator-account-id', 'name' => 'Delegator Account', 'email' => 'delegator@example.test'],
            requestedByAccount: ['accountIdentifier' => 'requested-by-account-id', 'name' => 'Requester Account', 'email' => 'requester@example.test'],
            status: 'rejected',
            direction: 'from_talent',
            requestedAt: '2026-09-10T10:00:00+00:00',
            approvedAt: null,
            rejectedAt: '2026-09-11T10:00:00+00:00',
        );

        $payload = $readModel->toArray();

        $this->assertNull($payload['approvedAt']);
        $this->assertSame('2026-09-11T10:00:00+00:00', $payload['rejectedAt']);
    }
}
