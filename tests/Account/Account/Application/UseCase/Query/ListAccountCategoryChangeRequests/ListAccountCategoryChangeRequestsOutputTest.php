<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestListItemReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountReadModel;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsOutput;

class ListAccountCategoryChangeRequestsOutputTest extends TestCase
{
    public function testToArrayReturnsRequests(): void
    {
        $output = new ListAccountCategoryChangeRequestsOutput();
        $output->output(
            [
                new AccountCategoryChangeRequestListItemReadModel(
                    request: new AccountCategoryChangeRequestReadModel(
                        requestIdentifier: 'request-id',
                        accountIdentifier: 'account-id',
                        currentAccountCategory: 'general',
                        requestedAccountCategory: 'agency',
                        status: 'approved',
                        requestedAt: '2026-08-11T10:00:00+00:00',
                        reviewedBy: 'reviewer-account-id',
                        reviewedAt: '2026-08-12T10:00:00+00:00',
                        rejectionReason: null,
                    ),
                    account: new AccountReadModel(
                        accountIdentifier: 'account-id',
                        email: 'account@example.com',
                        type: 'corporation',
                        name: 'Account Name',
                        status: 'active',
                        accountCategory: 'general',
                    ),
                ),
            ],
            2,
            5,
            41,
            10,
        );

        $this->assertSame([
            'requests' => [[
                'requestIdentifier' => 'request-id',
                'accountIdentifier' => 'account-id',
                'currentAccountCategory' => 'general',
                'requestedAccountCategory' => 'agency',
                'status' => 'approved',
                'requestedAt' => '2026-08-11T10:00:00+00:00',
                'reviewedBy' => 'reviewer-account-id',
                'reviewedAt' => '2026-08-12T10:00:00+00:00',
                'rejectionReason' => null,
                'account' => [
                    'accountIdentifier' => 'account-id',
                    'email' => 'account@example.com',
                    'type' => 'corporation',
                    'name' => 'Account Name',
                    'status' => 'active',
                    'accountCategory' => 'general',
                    'phone' => null,
                    'address' => null,
                ],
            ]],
            'current_page' => 2,
            'last_page' => 5,
            'total' => 41,
            'per_page' => 10,
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyRequestsByDefault(): void
    {
        $output = new ListAccountCategoryChangeRequestsOutput();

        $this->assertSame([
            'requests' => [],
            'current_page' => null,
            'last_page' => null,
            'total' => null,
            'per_page' => null,
        ], $output->toArray());
    }
}
