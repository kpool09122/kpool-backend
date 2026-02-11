<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\Repository\ImageHideRequestRepositoryInterface;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateImageHideRequest;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ImageHideRequestRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDのImageHideRequestが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $requestId = StrTestHelper::generateUuid();
        $imageId = StrTestHelper::generateUuid();

        CreateImageHideRequest::create($requestId, [
            'image_id' => $imageId,
            'requester_name' => 'Test Requester',
            'requester_email' => 'requester@example.com',
            'reason' => 'Privacy concern',
            'status' => ImageHideRequestStatus::PENDING->value,
        ]);

        $repository = $this->app->make(ImageHideRequestRepositoryInterface::class);
        $imageHideRequest = $repository->findById(new ImageHideRequestIdentifier($requestId));

        $this->assertInstanceOf(ImageHideRequest::class, $imageHideRequest);
        $this->assertSame($requestId, (string) $imageHideRequest->requestIdentifier());
        $this->assertSame($imageId, (string) $imageHideRequest->imageIdentifier());
        $this->assertSame('Test Requester', $imageHideRequest->requesterName());
        $this->assertSame('requester@example.com', $imageHideRequest->requesterEmail());
        $this->assertSame('Privacy concern', $imageHideRequest->reason());
        $this->assertSame(ImageHideRequestStatus::PENDING, $imageHideRequest->status());
        $this->assertInstanceOf(DateTimeImmutable::class, $imageHideRequest->requestedAt());
    }

    /**
     * 正常系：指定したIDのImageHideRequestが存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(ImageHideRequestRepositoryInterface::class);
        $imageHideRequest = $repository->findById(new ImageHideRequestIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($imageHideRequest);
    }

    /**
     * 正常系：レビュー済みのImageHideRequestが正しく取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithReviewedData(): void
    {
        $requestId = StrTestHelper::generateUuid();
        $imageId = StrTestHelper::generateUuid();
        $reviewerId = StrTestHelper::generateUuid();

        CreateImageHideRequest::create($requestId, [
            'image_id' => $imageId,
            'status' => ImageHideRequestStatus::APPROVED->value,
            'reviewer_id' => $reviewerId,
            'reviewed_at' => now(),
            'reviewer_comment' => 'Approved for valid reason.',
        ]);

        $repository = $this->app->make(ImageHideRequestRepositoryInterface::class);
        $imageHideRequest = $repository->findById(new ImageHideRequestIdentifier($requestId));

        $this->assertInstanceOf(ImageHideRequest::class, $imageHideRequest);
        $this->assertSame(ImageHideRequestStatus::APPROVED, $imageHideRequest->status());
        $this->assertSame($reviewerId, (string) $imageHideRequest->reviewerIdentifier());
        $this->assertInstanceOf(DateTimeImmutable::class, $imageHideRequest->reviewedAt());
        $this->assertSame('Approved for valid reason.', $imageHideRequest->reviewerComment());
    }

    /**
     * 正常系：正しくImageHideRequestを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $now = new DateTimeImmutable();

        $imageHideRequest = new ImageHideRequest(
            new ImageHideRequestIdentifier(StrTestHelper::generateUuid()),
            new ImageIdentifier(StrTestHelper::generateUuid()),
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            ImageHideRequestStatus::PENDING,
            $now,
            null,
            null,
            null,
        );

        $repository = $this->app->make(ImageHideRequestRepositoryInterface::class);
        $repository->save($imageHideRequest);

        $this->assertDatabaseHas('image_hide_requests', [
            'id' => (string) $imageHideRequest->requestIdentifier(),
            'image_id' => (string) $imageHideRequest->imageIdentifier(),
            'requester_name' => 'Test Requester',
            'requester_email' => 'requester@example.com',
            'reason' => 'Privacy concern',
            'status' => ImageHideRequestStatus::PENDING->value,
            'reviewer_id' => null,
            'reviewed_at' => null,
            'reviewer_comment' => null,
        ]);
    }

    /**
     * 正常系：既存のImageHideRequestを更新できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdate(): void
    {
        $requestId = StrTestHelper::generateUuid();
        $imageId = StrTestHelper::generateUuid();

        CreateImageHideRequest::create($requestId, [
            'image_id' => $imageId,
            'status' => ImageHideRequestStatus::PENDING->value,
        ]);

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $now = new DateTimeImmutable();

        $imageHideRequest = new ImageHideRequest(
            new ImageHideRequestIdentifier($requestId),
            new ImageIdentifier($imageId),
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            ImageHideRequestStatus::APPROVED,
            $now,
            $reviewerIdentifier,
            $now,
            'Approved for valid reason.',
        );

        $repository = $this->app->make(ImageHideRequestRepositoryInterface::class);
        $repository->save($imageHideRequest);

        $this->assertDatabaseHas('image_hide_requests', [
            'id' => $requestId,
            'status' => ImageHideRequestStatus::APPROVED->value,
            'reviewer_id' => (string) $reviewerIdentifier,
            'reviewer_comment' => 'Approved for valid reason.',
        ]);

        $this->assertDatabaseCount('image_hide_requests', 1);
    }

    /**
     * 正常系：指定した画像にPendingのリクエストが存在する場合、trueが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPendingByImageIdReturnsTrue(): void
    {
        $imageId = StrTestHelper::generateUuid();

        CreateImageHideRequest::create(StrTestHelper::generateUuid(), [
            'image_id' => $imageId,
            'status' => ImageHideRequestStatus::PENDING->value,
        ]);

        $repository = $this->app->make(ImageHideRequestRepositoryInterface::class);
        $result = $repository->existsPendingByImageId(new ImageIdentifier($imageId));

        $this->assertTrue($result);
    }

    /**
     * 正常系：指定した画像にPendingのリクエストが存在しない場合、falseが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPendingByImageIdReturnsFalseWhenNotExist(): void
    {
        $repository = $this->app->make(ImageHideRequestRepositoryInterface::class);
        $result = $repository->existsPendingByImageId(new ImageIdentifier(StrTestHelper::generateUuid()));

        $this->assertFalse($result);
    }

    /**
     * 正常系：指定した画像にApprovedのリクエストのみ存在する場合、falseが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPendingByImageIdReturnsFalseWhenOnlyApproved(): void
    {
        $imageId = StrTestHelper::generateUuid();

        CreateImageHideRequest::create(StrTestHelper::generateUuid(), [
            'image_id' => $imageId,
            'status' => ImageHideRequestStatus::APPROVED->value,
        ]);

        $repository = $this->app->make(ImageHideRequestRepositoryInterface::class);
        $result = $repository->existsPendingByImageId(new ImageIdentifier($imageId));

        $this->assertFalse($result);
    }

    /**
     * 正常系：指定した画像にRejectedのリクエストのみ存在する場合、falseが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPendingByImageIdReturnsFalseWhenOnlyRejected(): void
    {
        $imageId = StrTestHelper::generateUuid();

        CreateImageHideRequest::create(StrTestHelper::generateUuid(), [
            'image_id' => $imageId,
            'status' => ImageHideRequestStatus::REJECTED->value,
        ]);

        $repository = $this->app->make(ImageHideRequestRepositoryInterface::class);
        $result = $repository->existsPendingByImageId(new ImageIdentifier($imageId));

        $this->assertFalse($result);
    }
}
