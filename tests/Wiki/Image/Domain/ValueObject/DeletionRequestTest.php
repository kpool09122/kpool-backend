<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestNotPendingException;
use Source\Wiki\Image\Domain\ValueObject\DeletionRequest;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class DeletionRequestTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $deletionRequest = $this->createPendingDeletionRequest();

        $this->assertSame('Test Requester', $deletionRequest->requesterName());
        $this->assertSame('requester@example.com', $deletionRequest->requesterEmail());
        $this->assertSame('Privacy concern', $deletionRequest->reason());
        $this->assertInstanceOf(DateTimeImmutable::class, $deletionRequest->requestedAt());
        $this->assertNull($deletionRequest->reviewerIdentifier());
        $this->assertNull($deletionRequest->reviewedAt());
        $this->assertNull($deletionRequest->reviewerComment());
    }

    /**
     * 正常系: approveが正しく動作すること.
     */
    public function testApprove(): void
    {
        $deletionRequest = $this->createPendingDeletionRequest();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $approved = $deletionRequest->approve($reviewerIdentifier, 'Approved for privacy');

        $this->assertSame((string) $reviewerIdentifier, (string) $approved->reviewerIdentifier());
        $this->assertInstanceOf(DateTimeImmutable::class, $approved->reviewedAt());
        $this->assertSame('Approved for privacy', $approved->reviewerComment());
        // 元のインスタンスは変わらないこと
        $this->assertNull($deletionRequest->reviewedAt());
    }

    /**
     * 正常系: rejectが正しく動作すること.
     */
    public function testReject(): void
    {
        $deletionRequest = $this->createPendingDeletionRequest();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $rejected = $deletionRequest->reject($reviewerIdentifier, 'Not applicable');

        $this->assertSame((string) $reviewerIdentifier, (string) $rejected->reviewerIdentifier());
        $this->assertInstanceOf(DateTimeImmutable::class, $rejected->reviewedAt());
        $this->assertSame('Not applicable', $rejected->reviewerComment());
        // 元のインスタンスは変わらないこと
        $this->assertNull($deletionRequest->reviewedAt());
    }

    /**
     * 異常系: レビュー済み状態でapproveするとImageDeletionRequestNotPendingExceptionがスローされること.
     */
    public function testApproveThrowsWhenNotPending(): void
    {
        $deletionRequest = $this->createApprovedDeletionRequest();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(ImageDeletionRequestNotPendingException::class);
        $deletionRequest->approve($reviewerIdentifier, 'comment');
    }

    /**
     * 異常系: レビュー済み状態でrejectするとImageDeletionRequestNotPendingExceptionがスローされること.
     */
    public function testRejectThrowsWhenNotPending(): void
    {
        $deletionRequest = $this->createApprovedDeletionRequest();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(ImageDeletionRequestNotPendingException::class);
        $deletionRequest->reject($reviewerIdentifier, 'comment');
    }

    private function createPendingDeletionRequest(): DeletionRequest
    {
        return new DeletionRequest(
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }

    private function createApprovedDeletionRequest(): DeletionRequest
    {
        return new DeletionRequest(
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            new DateTimeImmutable(),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            'Approved',
        );
    }
}
