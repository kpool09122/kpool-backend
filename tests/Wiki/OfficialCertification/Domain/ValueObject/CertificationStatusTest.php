<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;

class CertificationStatusTest extends TestCase
{
    /**
     * 正常系: isPendingが正しく動作すること.
     *
     * @return void
     */
    public function testIsPending(): void
    {
        $this->assertTrue(CertificationStatus::PENDING->isPending());
        $this->assertFalse(CertificationStatus::APPROVED->isPending());
        $this->assertFalse(CertificationStatus::REJECTED->isPending());
    }

    /**
     * 正常系: isApprovedが正しく動作すること.
     *
     * @return void
     */
    public function testIsApproved(): void
    {
        $this->assertFalse(CertificationStatus::PENDING->isApproved());
        $this->assertTrue(CertificationStatus::APPROVED->isApproved());
        $this->assertFalse(CertificationStatus::REJECTED->isApproved());
    }

    /**
     * 正常系: isRejectedが正しく動作すること.
     *
     * @return void
     */
    public function testIsRejected(): void
    {
        $this->assertFalse(CertificationStatus::PENDING->isRejected());
        $this->assertFalse(CertificationStatus::APPROVED->isRejected());
        $this->assertTrue(CertificationStatus::REJECTED->isRejected());
    }
}
