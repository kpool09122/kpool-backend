<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;

class InvitationStatusTest extends TestCase
{
    public function testIsPending(): void
    {
        $this->assertTrue(InvitationStatus::PENDING->isPending());
        $this->assertFalse(InvitationStatus::ACCEPTED->isPending());
        $this->assertFalse(InvitationStatus::REVOKED->isPending());
    }

    public function testIsAccepted(): void
    {
        $this->assertFalse(InvitationStatus::PENDING->isAccepted());
        $this->assertTrue(InvitationStatus::ACCEPTED->isAccepted());
        $this->assertFalse(InvitationStatus::REVOKED->isAccepted());
    }

    public function testIsRevoked(): void
    {
        $this->assertFalse(InvitationStatus::PENDING->isRevoked());
        $this->assertFalse(InvitationStatus::ACCEPTED->isRevoked());
        $this->assertTrue(InvitationStatus::REVOKED->isRevoked());
    }
}
