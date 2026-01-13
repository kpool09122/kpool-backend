<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReasonCode;

class RejectionReasonCodeTest extends TestCase
{
    /**
     * 正常系: requiresDetailがOTHERの場合のみtrueを返すこと.
     *
     * @return void
     */
    public function testRequiresDetail(): void
    {
        $this->assertTrue(RejectionReasonCode::OTHER->requiresDetail());

        $this->assertFalse(RejectionReasonCode::DOCUMENT_UNCLEAR->requiresDetail());
        $this->assertFalse(RejectionReasonCode::DOCUMENT_EXPIRED->requiresDetail());
        $this->assertFalse(RejectionReasonCode::DOCUMENT_MISMATCH->requiresDetail());
        $this->assertFalse(RejectionReasonCode::DOCUMENT_INCOMPLETE->requiresDetail());
        $this->assertFalse(RejectionReasonCode::FRAUDULENT_DOCUMENT->requiresDetail());
    }
}
