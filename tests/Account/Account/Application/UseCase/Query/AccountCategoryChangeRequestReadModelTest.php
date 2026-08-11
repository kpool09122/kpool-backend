<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;

class AccountCategoryChangeRequestReadModelTest extends TestCase
{
    public function testToArrayReturnsRequestFields(): void
    {
        $readModel = new AccountCategoryChangeRequestReadModel(
            requestIdentifier: 'request-id',
            accountIdentifier: 'account-id',
            currentAccountCategory: 'general',
            requestedAccountCategory: 'agency',
            status: 'rejected',
            requestedAt: '2026-08-11T10:00:00+00:00',
            reviewedBy: 'reviewer-account-id',
            reviewedAt: '2026-08-12T10:00:00+00:00',
            rejectionReason: ['code' => 'other', 'detail' => '書類不足'],
        );

        $this->assertSame([
            'requestIdentifier' => 'request-id',
            'accountIdentifier' => 'account-id',
            'currentAccountCategory' => 'general',
            'requestedAccountCategory' => 'agency',
            'status' => 'rejected',
            'requestedAt' => '2026-08-11T10:00:00+00:00',
            'reviewedBy' => 'reviewer-account-id',
            'reviewedAt' => '2026-08-12T10:00:00+00:00',
            'rejectionReason' => ['code' => 'other', 'detail' => '書類不足'],
        ], $readModel->toArray());
    }

    public function testToArrayReturnsNullableReviewFields(): void
    {
        $readModel = new AccountCategoryChangeRequestReadModel(
            requestIdentifier: 'request-id',
            accountIdentifier: 'account-id',
            currentAccountCategory: 'general',
            requestedAccountCategory: 'talent',
            status: 'pending',
            requestedAt: '2026-08-11T10:00:00+00:00',
            reviewedBy: null,
            reviewedAt: null,
            rejectionReason: null,
        );

        $this->assertSame([
            'requestIdentifier' => 'request-id',
            'accountIdentifier' => 'account-id',
            'currentAccountCategory' => 'general',
            'requestedAccountCategory' => 'talent',
            'status' => 'pending',
            'requestedAt' => '2026-08-11T10:00:00+00:00',
            'reviewedBy' => null,
            'reviewedAt' => null,
            'rejectionReason' => null,
        ], $readModel->toArray());
    }
}
