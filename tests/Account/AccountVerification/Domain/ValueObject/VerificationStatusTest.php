<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;

class VerificationStatusTest extends TestCase
{
    /**
     * 正常系: isPendingが正しく動作すること.
     *
     * @return void
     */
    public function testIsPending(): void
    {
        $status = VerificationStatus::PENDING;

        $this->assertTrue($status->isPending());
        $this->assertFalse($status->isApproved());
        $this->assertFalse($status->isRejected());
    }

    /**
     * 正常系: isApprovedが正しく動作すること.
     *
     * @return void
     */
    public function testIsApproved(): void
    {
        $status = VerificationStatus::APPROVED;

        $this->assertFalse($status->isPending());
        $this->assertTrue($status->isApproved());
        $this->assertFalse($status->isRejected());
    }

    /**
     * 正常系: isRejectedが正しく動作すること.
     *
     * @return void
     */
    public function testIsRejected(): void
    {
        $status = VerificationStatus::REJECTED;

        $this->assertFalse($status->isPending());
        $this->assertFalse($status->isApproved());
        $this->assertTrue($status->isRejected());
    }

    /**
     * 正常系: PENDING状態から遷移可能なステータスが正しいこと.
     *
     * @return void
     */
    public function testCanTransitionFromPending(): void
    {
        $status = VerificationStatus::PENDING;

        $this->assertTrue($status->canTransitionTo(VerificationStatus::APPROVED));
        $this->assertTrue($status->canTransitionTo(VerificationStatus::REJECTED));
        $this->assertFalse($status->canTransitionTo(VerificationStatus::PENDING));
    }

    /**
     * 正常系: APPROVED状態から遷移不可であること.
     *
     * @return void
     */
    public function testCannotTransitionFromApproved(): void
    {
        $status = VerificationStatus::APPROVED;

        $this->assertFalse($status->canTransitionTo(VerificationStatus::PENDING));
        $this->assertFalse($status->canTransitionTo(VerificationStatus::APPROVED));
        $this->assertFalse($status->canTransitionTo(VerificationStatus::REJECTED));
    }

    /**
     * 正常系: REJECTED状態から遷移不可であること.
     *
     * @return void
     */
    public function testCannotTransitionFromRejected(): void
    {
        $status = VerificationStatus::REJECTED;

        $this->assertFalse($status->canTransitionTo(VerificationStatus::PENDING));
        $this->assertFalse($status->canTransitionTo(VerificationStatus::APPROVED));
        $this->assertFalse($status->canTransitionTo(VerificationStatus::REJECTED));
    }
}
