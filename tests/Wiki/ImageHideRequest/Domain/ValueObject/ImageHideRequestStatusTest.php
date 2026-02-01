<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;

class ImageHideRequestStatusTest extends TestCase
{
    /**
     * 正常系: isPendingが正しく動作すること.
     *
     * @return void
     */
    public function testIsPending(): void
    {
        $this->assertTrue(ImageHideRequestStatus::PENDING->isPending());
        $this->assertFalse(ImageHideRequestStatus::PENDING->isApproved());
        $this->assertFalse(ImageHideRequestStatus::PENDING->isRejected());
    }

    /**
     * 正常系: isApprovedが正しく動作すること.
     *
     * @return void
     */
    public function testIsApproved(): void
    {
        $this->assertFalse(ImageHideRequestStatus::APPROVED->isPending());
        $this->assertTrue(ImageHideRequestStatus::APPROVED->isApproved());
        $this->assertFalse(ImageHideRequestStatus::APPROVED->isRejected());
    }

    /**
     * 正常系: isRejectedが正しく動作すること.
     *
     * @return void
     */
    public function testIsRejected(): void
    {
        $this->assertFalse(ImageHideRequestStatus::REJECTED->isPending());
        $this->assertFalse(ImageHideRequestStatus::REJECTED->isApproved());
        $this->assertTrue(ImageHideRequestStatus::REJECTED->isRejected());
    }
}
