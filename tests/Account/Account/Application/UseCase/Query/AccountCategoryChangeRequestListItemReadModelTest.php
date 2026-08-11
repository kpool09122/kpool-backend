<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestListItemReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountReadModel;

class AccountCategoryChangeRequestListItemReadModelTest extends TestCase
{
    public function testToArrayReturnsRequestWithAccount(): void
    {
        $readModel = new AccountCategoryChangeRequestListItemReadModel(
            request: new AccountCategoryChangeRequestReadModel(
                requestIdentifier: 'request-id',
                accountIdentifier: 'account-id',
                currentAccountCategory: 'general',
                requestedAccountCategory: 'agency',
                status: 'pending',
                requestedAt: '2026-08-11T10:00:00+00:00',
                reviewedBy: null,
                reviewedAt: null,
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
        );

        $this->assertSame([
            'requestIdentifier' => 'request-id',
            'accountIdentifier' => 'account-id',
            'currentAccountCategory' => 'general',
            'requestedAccountCategory' => 'agency',
            'status' => 'pending',
            'requestedAt' => '2026-08-11T10:00:00+00:00',
            'reviewedBy' => null,
            'reviewedAt' => null,
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
        ], $readModel->toArray());
    }
}
