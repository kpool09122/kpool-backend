<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReason;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReasonCode;

class RejectionReasonTest extends TestCase
{
    public function test__construct(): void
    {
        $reason = new RejectionReason(RejectionReasonCode::DOCUMENT_UNCLEAR, 'The image is blurry');

        $this->assertSame(RejectionReasonCode::DOCUMENT_UNCLEAR, $reason->code());
        $this->assertSame('The image is blurry', $reason->detail());
    }

    public function testWithoutDetail(): void
    {
        $reason = new RejectionReason(RejectionReasonCode::DOCUMENT_EXPIRED);

        $this->assertSame(RejectionReasonCode::DOCUMENT_EXPIRED, $reason->code());
        $this->assertNull($reason->detail());
    }

    public function testOtherRequiresDetail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RejectionReason(RejectionReasonCode::OTHER);
    }

    public function testOtherWithEmptyDetail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RejectionReason(RejectionReasonCode::OTHER, '');
    }

    public function testOtherWithDetail(): void
    {
        $reason = new RejectionReason(RejectionReasonCode::OTHER, 'Custom rejection reason');

        $this->assertSame(RejectionReasonCode::OTHER, $reason->code());
        $this->assertSame('Custom rejection reason', $reason->detail());
    }

    public function testDetailTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RejectionReason(RejectionReasonCode::OTHER, str_repeat('a', 1001));
    }

    public function testToArray(): void
    {
        $reason = new RejectionReason(RejectionReasonCode::DOCUMENT_UNCLEAR, 'The image is blurry');
        $array = $reason->toArray();

        $this->assertSame('document_unclear', $array['code']);
        $this->assertSame('The image is blurry', $array['detail']);
    }

    public function testFromArray(): void
    {
        $reason = RejectionReason::fromArray([
            'code' => 'document_unclear',
            'detail' => 'The image is blurry',
        ]);

        $this->assertSame(RejectionReasonCode::DOCUMENT_UNCLEAR, $reason->code());
        $this->assertSame('The image is blurry', $reason->detail());
    }
}
