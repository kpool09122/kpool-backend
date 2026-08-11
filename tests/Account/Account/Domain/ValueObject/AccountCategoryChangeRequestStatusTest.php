<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;

class AccountCategoryChangeRequestStatusTest extends TestCase
{
    public function testIsPending(): void
    {
        $this->assertTrue(AccountCategoryChangeRequestStatus::PENDING->isPending());
        $this->assertFalse(AccountCategoryChangeRequestStatus::APPROVED->isPending());
        $this->assertFalse(AccountCategoryChangeRequestStatus::REJECTED->isPending());
    }

    public function testCanTransitionFromPending(): void
    {
        $status = AccountCategoryChangeRequestStatus::PENDING;

        $this->assertTrue($status->canTransitionTo(AccountCategoryChangeRequestStatus::APPROVED));
        $this->assertTrue($status->canTransitionTo(AccountCategoryChangeRequestStatus::REJECTED));
        $this->assertFalse($status->canTransitionTo(AccountCategoryChangeRequestStatus::PENDING));
    }

    public function testCannotTransitionFromApproved(): void
    {
        $status = AccountCategoryChangeRequestStatus::APPROVED;

        $this->assertFalse($status->canTransitionTo(AccountCategoryChangeRequestStatus::PENDING));
        $this->assertFalse($status->canTransitionTo(AccountCategoryChangeRequestStatus::APPROVED));
        $this->assertFalse($status->canTransitionTo(AccountCategoryChangeRequestStatus::REJECTED));
    }

    public function testCannotTransitionFromRejected(): void
    {
        $status = AccountCategoryChangeRequestStatus::REJECTED;

        $this->assertFalse($status->canTransitionTo(AccountCategoryChangeRequestStatus::PENDING));
        $this->assertFalse($status->canTransitionTo(AccountCategoryChangeRequestStatus::APPROVED));
        $this->assertFalse($status->canTransitionTo(AccountCategoryChangeRequestStatus::REJECTED));
    }
}
