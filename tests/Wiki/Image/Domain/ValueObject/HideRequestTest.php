<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Domain\Exception\ImageHideRequestNotPendingException;
use Source\Wiki\Image\Domain\ValueObject\HideRequest;
use Source\Wiki\Image\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class HideRequestTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $hideRequest = $this->createPendingHideRequest();

        $this->assertSame('Test Requester', $hideRequest->requesterName());
        $this->assertSame('requester@example.com', $hideRequest->requesterEmail());
        $this->assertSame('Privacy concern', $hideRequest->reason());
        $this->assertSame(ImageHideRequestStatus::PENDING, $hideRequest->status());
        $this->assertInstanceOf(DateTimeImmutable::class, $hideRequest->requestedAt());
        $this->assertNull($hideRequest->reviewerIdentifier());
        $this->assertNull($hideRequest->reviewedAt());
        $this->assertNull($hideRequest->reviewerComment());
    }

    /**
     * 正常系: approveが正しく動作すること.
     */
    public function testApprove(): void
    {
        $hideRequest = $this->createPendingHideRequest();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $approved = $hideRequest->approve($reviewerIdentifier, 'Approved for privacy');

        $this->assertSame(ImageHideRequestStatus::APPROVED, $approved->status());
        $this->assertSame((string) $reviewerIdentifier, (string) $approved->reviewerIdentifier());
        $this->assertInstanceOf(DateTimeImmutable::class, $approved->reviewedAt());
        $this->assertSame('Approved for privacy', $approved->reviewerComment());
        // 元のインスタンスは変わらないこと
        $this->assertSame(ImageHideRequestStatus::PENDING, $hideRequest->status());
    }

    /**
     * 正常系: rejectが正しく動作すること.
     */
    public function testReject(): void
    {
        $hideRequest = $this->createPendingHideRequest();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $rejected = $hideRequest->reject($reviewerIdentifier, 'Not applicable');

        $this->assertSame(ImageHideRequestStatus::REJECTED, $rejected->status());
        $this->assertSame((string) $reviewerIdentifier, (string) $rejected->reviewerIdentifier());
        $this->assertInstanceOf(DateTimeImmutable::class, $rejected->reviewedAt());
        $this->assertSame('Not applicable', $rejected->reviewerComment());
        // 元のインスタンスは変わらないこと
        $this->assertSame(ImageHideRequestStatus::PENDING, $hideRequest->status());
    }

    /**
     * 異常系: APPROVED状態でapproveするとImageHideRequestNotPendingExceptionがスローされること.
     */
    public function testApproveThrowsWhenNotPending(): void
    {
        $hideRequest = $this->createApprovedHideRequest();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(ImageHideRequestNotPendingException::class);
        $hideRequest->approve($reviewerIdentifier, 'comment');
    }

    /**
     * 異常系: REJECTED状態でrejectするとImageHideRequestNotPendingExceptionがスローされること.
     */
    public function testRejectThrowsWhenNotPending(): void
    {
        $hideRequest = $this->createApprovedHideRequest();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(ImageHideRequestNotPendingException::class);
        $hideRequest->reject($reviewerIdentifier, 'comment');
    }

    private function createPendingHideRequest(): HideRequest
    {
        return new HideRequest(
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            ImageHideRequestStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }

    private function createApprovedHideRequest(): HideRequest
    {
        return new HideRequest(
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            ImageHideRequestStatus::APPROVED,
            new DateTimeImmutable(),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            'Approved',
        );
    }
}
