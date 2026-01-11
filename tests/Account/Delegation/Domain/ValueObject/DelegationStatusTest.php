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

    public function testRevokedValue(): void
    {
        $this->assertSame('revoked', DelegationStatus::REVOKED->value);
    }

    public function testIsPending(): void
    {
        $this->assertTrue(DelegationStatus::PENDING->isPending());
        $this->assertFalse(DelegationStatus::APPROVED->isPending());
        $this->assertFalse(DelegationStatus::REVOKED->isPending());
    }

    public function testIsApproved(): void
    {
        $this->assertFalse(DelegationStatus::PENDING->isApproved());
        $this->assertTrue(DelegationStatus::APPROVED->isApproved());
        $this->assertFalse(DelegationStatus::REVOKED->isApproved());
    }

    public function testIsRevoked(): void
    {
        $this->assertFalse(DelegationStatus::PENDING->isRevoked());
        $this->assertFalse(DelegationStatus::APPROVED->isRevoked());
        $this->assertTrue(DelegationStatus::REVOKED->isRevoked());
    }
}
