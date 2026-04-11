<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\Exception\ImageHideRequestNotPendingForApprovalException;
use Source\Wiki\ImageHideRequest\Domain\Exception\ImageHideRequestNotPendingForRejectionException;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ImageHideRequestTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $requestIdentifier = new ImageHideRequestIdentifier(StrTestHelper::generateUuid());
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $requesterName = 'Test Requester';
        $requesterEmail = 'requester@example.com';
        $reason = 'Privacy concern';
        $status = ImageHideRequestStatus::PENDING;
        $requestedAt = new DateTimeImmutable();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $reviewedAt = new DateTimeImmutable();
        $reviewerComment = 'Reviewed comment';

        $imageHideRequest = new ImageHideRequest(
            $requestIdentifier,
            $imageIdentifier,
            $requesterName,
            $requesterEmail,
            $reason,
            $status,
            $requestedAt,
            $reviewerIdentifier,
            $reviewedAt,
            $reviewerComment,
        );

        $this->assertSame((string) $requestIdentifier, (string) $imageHideRequest->requestIdentifier());
        $this->assertSame((string) $imageIdentifier, (string) $imageHideRequest->imageIdentifier());
        $this->assertSame($requesterName, $imageHideRequest->requesterName());
        $this->assertSame($requesterEmail, $imageHideRequest->requesterEmail());
        $this->assertSame($reason, $imageHideRequest->reason());
        $this->assertSame($status, $imageHideRequest->status());
        $this->assertSame($requestedAt, $imageHideRequest->requestedAt());
        $this->assertSame((string) $reviewerIdentifier, (string) $imageHideRequest->reviewerIdentifier());
        $this->assertSame($reviewedAt, $imageHideRequest->reviewedAt());
        $this->assertSame($reviewerComment, $imageHideRequest->reviewerComment());
    }

    /**
     * 正常系: approve()が正しく動作すること.
     */
    public function testApprove(): void
    {
        $imageHideRequest = $this->createDummyImageHideRequest();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $reviewerComment = 'Approved for valid reason.';

        $this->assertTrue($imageHideRequest->isPending());
        $this->assertNull($imageHideRequest->reviewerIdentifier());
        $this->assertNull($imageHideRequest->reviewedAt());
        $this->assertNull($imageHideRequest->reviewerComment());

        $imageHideRequest->approve($reviewerIdentifier, $reviewerComment);

        $this->assertSame(ImageHideRequestStatus::APPROVED, $imageHideRequest->status());
        $this->assertTrue($imageHideRequest->isApproved());
        $this->assertFalse($imageHideRequest->isPending());
        $this->assertFalse($imageHideRequest->isRejected());
        $this->assertSame((string) $reviewerIdentifier, (string) $imageHideRequest->reviewerIdentifier());
        $this->assertInstanceOf(DateTimeImmutable::class, $imageHideRequest->reviewedAt());
        $this->assertSame($reviewerComment, $imageHideRequest->reviewerComment());
    }

    /**
     * 異常系: Pending以外の状態でapprove()を呼ぶとDomainExceptionがスローされること.
     */
    public function testApproveThrowsExceptionWhenNotPending(): void
    {
        $imageHideRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::APPROVED);
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(ImageHideRequestNotPendingForApprovalException::class);
        $this->expectExceptionMessage('Only pending requests can be approved.');

        $imageHideRequest->approve($reviewerIdentifier, 'comment');
    }

    /**
     * 正常系: reject()が正しく動作すること.
     */
    public function testReject(): void
    {
        $imageHideRequest = $this->createDummyImageHideRequest();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $reviewerComment = 'Rejected for invalid reason.';

        $this->assertTrue($imageHideRequest->isPending());
        $this->assertNull($imageHideRequest->reviewerIdentifier());
        $this->assertNull($imageHideRequest->reviewedAt());
        $this->assertNull($imageHideRequest->reviewerComment());

        $imageHideRequest->reject($reviewerIdentifier, $reviewerComment);

        $this->assertSame(ImageHideRequestStatus::REJECTED, $imageHideRequest->status());
        $this->assertTrue($imageHideRequest->isRejected());
        $this->assertFalse($imageHideRequest->isPending());
        $this->assertFalse($imageHideRequest->isApproved());
        $this->assertSame((string) $reviewerIdentifier, (string) $imageHideRequest->reviewerIdentifier());
        $this->assertInstanceOf(DateTimeImmutable::class, $imageHideRequest->reviewedAt());
        $this->assertSame($reviewerComment, $imageHideRequest->reviewerComment());
    }

    /**
     * 異常系: Pending以外の状態でreject()を呼ぶとDomainExceptionがスローされること.
     */
    public function testRejectThrowsExceptionWhenNotPending(): void
    {
        $imageHideRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::REJECTED);
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(ImageHideRequestNotPendingForRejectionException::class);
        $this->expectExceptionMessage('Only pending requests can be rejected.');

        $imageHideRequest->reject($reviewerIdentifier, 'comment');
    }

    /**
     * 正常系: isPending()が正しく動作すること.
     */
    public function testIsPending(): void
    {
        $pendingRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::PENDING);
        $approvedRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::APPROVED);
        $rejectedRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::REJECTED);

        $this->assertTrue($pendingRequest->isPending());
        $this->assertFalse($approvedRequest->isPending());
        $this->assertFalse($rejectedRequest->isPending());
    }

    /**
     * 正常系: isApproved()が正しく動作すること.
     */
    public function testIsApproved(): void
    {
        $pendingRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::PENDING);
        $approvedRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::APPROVED);
        $rejectedRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::REJECTED);

        $this->assertFalse($pendingRequest->isApproved());
        $this->assertTrue($approvedRequest->isApproved());
        $this->assertFalse($rejectedRequest->isApproved());
    }

    /**
     * 正常系: isRejected()が正しく動作すること.
     */
    public function testIsRejected(): void
    {
        $pendingRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::PENDING);
        $approvedRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::APPROVED);
        $rejectedRequest = $this->createDummyImageHideRequest(ImageHideRequestStatus::REJECTED);

        $this->assertFalse($pendingRequest->isRejected());
        $this->assertFalse($approvedRequest->isRejected());
        $this->assertTrue($rejectedRequest->isRejected());
    }

    private function createDummyImageHideRequest(
        ImageHideRequestStatus $status = ImageHideRequestStatus::PENDING,
    ): ImageHideRequest {
        return new ImageHideRequest(
            new ImageHideRequestIdentifier(StrTestHelper::generateUuid()),
            new ImageIdentifier(StrTestHelper::generateUuid()),
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            $status,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }
}
