<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;

class DelegationStatusTest extends TestCase
{
    public function testPendingValue(): void
    {
        $this->assertSame('pending', DelegationStatus::PENDING->value);
    }

    public function testApprovedValue(): void
    {
        $this->assertSame('approved', DelegationStatus::APPROVED->value);
    }

    public function testRejectedValue(): void
    {
        $this->assertSame('rejected', DelegationStatus::REJECTED->value);
    }

    public function testIsPending(): void
    {
        $this->assertTrue(DelegationStatus::PENDING->isPending());
        $this->assertFalse(DelegationStatus::APPROVED->isPending());
        $this->assertFalse(DelegationStatus::REJECTED->isPending());
    }

    public function testIsApproved(): void
    {
        $this->assertFalse(DelegationStatus::PENDING->isApproved());
        $this->assertTrue(DelegationStatus::APPROVED->isApproved());
        $this->assertFalse(DelegationStatus::REJECTED->isApproved());
    }

    public function testIsRejected(): void
    {
        $this->assertFalse(DelegationStatus::PENDING->isRejected());
        $this->assertFalse(DelegationStatus::APPROVED->isRejected());
        $this->assertTrue(DelegationStatus::REJECTED->isRejected());
    }
}
