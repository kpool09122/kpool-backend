<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Query\ListDelegations;

use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Application\UseCase\Query\DelegationReadModel;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsOutput;

class ListDelegationsOutputTest extends TestCase
{
    public function testToArrayReturnsDelegationsAndPagination(): void
    {
        $output = new ListDelegationsOutput();
        $output->output([new DelegationReadModel(
            'delegation-id',
            'affiliation-id',
            'delegate-id',
            'delegator-id',
            'requester-id',
            'approved',
            'from_agency',
            '2026-09-12T10:00:00+00:00',
            '2026-09-12T11:00:00+00:00',
            null,
        )], 2, 5, 41, 10);

        $this->assertSame([
            'delegations' => [[
                'delegationIdentifier' => 'delegation-id',
                'affiliationIdentifier' => 'affiliation-id',
                'delegateAccountIdentifier' => 'delegate-id',
                'delegatorAccountIdentifier' => 'delegator-id',
                'requestedByAccountIdentifier' => 'requester-id',
                'status' => 'approved',
                'direction' => 'from_agency',
                'requestedAt' => '2026-09-12T10:00:00+00:00',
                'approvedAt' => '2026-09-12T11:00:00+00:00',
                'revokedAt' => null,
            ]],
            'current_page' => 2, 'last_page' => 5, 'total' => 41, 'per_page' => 10,
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyDefaults(): void
    {
        $this->assertSame([
            'delegations' => [], 'current_page' => null, 'last_page' => null, 'total' => null, 'per_page' => null,
        ], (new ListDelegationsOutput())->toArray());
    }
}
