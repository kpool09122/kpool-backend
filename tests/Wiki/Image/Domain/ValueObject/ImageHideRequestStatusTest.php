<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Domain\ValueObject\ImageHideRequestStatus;

class ImageHideRequestStatusTest extends TestCase
{
    /**
     * 正常系: PENDINGステータスが正しく判定されること.
     */
    public function testIsPending(): void
    {
        $this->assertTrue(ImageHideRequestStatus::PENDING->isPending());
        $this->assertFalse(ImageHideRequestStatus::APPROVED->isPending());
        $this->assertFalse(ImageHideRequestStatus::REJECTED->isPending());
    }

    /**
     * 正常系: APPROVEDステータスが正しく判定されること.
     */
    public function testIsApproved(): void
    {
        $this->assertFalse(ImageHideRequestStatus::PENDING->isApproved());
        $this->assertTrue(ImageHideRequestStatus::APPROVED->isApproved());
        $this->assertFalse(ImageHideRequestStatus::REJECTED->isApproved());
    }

    /**
     * 正常系: REJECTEDステータスが正しく判定されること.
     */
    public function testIsRejected(): void
    {
        $this->assertFalse(ImageHideRequestStatus::PENDING->isRejected());
        $this->assertFalse(ImageHideRequestStatus::APPROVED->isRejected());
        $this->assertTrue(ImageHideRequestStatus::REJECTED->isRejected());
    }

    /**
     * 正常系: 文字列から正しく生成されること.
     */
    public function testFromValue(): void
    {
        $this->assertSame(ImageHideRequestStatus::PENDING, ImageHideRequestStatus::from('pending'));
        $this->assertSame(ImageHideRequestStatus::APPROVED, ImageHideRequestStatus::from('approved'));
        $this->assertSame(ImageHideRequestStatus::REJECTED, ImageHideRequestStatus::from('rejected'));
    }
}
