<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Exception\ImageHideRequestAlreadyPendingException;
use Source\Wiki\Image\Domain\Exception\ImageHideRequestNotPendingException;
use Source\Wiki\Image\Domain\ValueObject\HideRequest;
use Source\Wiki\Image\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class ImageTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $isHidden = true;
        $hiddenBy = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $hiddenAt = new DateTimeImmutable();
        $uploaderIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $uploadedAt = new DateTimeImmutable();
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approvedAt = new DateTimeImmutable();
        $updaterIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $updatedAt = new DateTimeImmutable();

        $image = new Image(
            $imageIdentifier,
            $resourceType,
            $resourceIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $isHidden,
            $hiddenBy,
            $hiddenAt,
            $uploaderIdentifier,
            $uploadedAt,
            $approverIdentifier,
            $approvedAt,
            $updaterIdentifier,
            $updatedAt,
        );

        $this->assertSame((string) $imageIdentifier, (string) $image->imageIdentifier());
        $this->assertSame($resourceType, $image->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $image->wikiIdentifier());
        $this->assertSame((string) $imagePath, (string) $image->imagePath());
        $this->assertSame($imageUsage, $image->imageUsage());
        $this->assertSame($displayOrder, $image->displayOrder());
        $this->assertSame($sourceUrl, $image->sourceUrl());
        $this->assertSame($sourceName, $image->sourceName());
        $this->assertSame($altText, $image->altText());
        $this->assertSame($isHidden, $image->isHidden());
        $this->assertSame((string) $hiddenBy, (string) $image->hiddenBy());
        $this->assertSame($hiddenAt, $image->hiddenAt());
        $this->assertSame((string) $uploaderIdentifier, (string) $image->uploaderIdentifier());
        $this->assertSame($uploadedAt, $image->uploadedAt());
        $this->assertSame((string) $approverIdentifier, (string) $image->approverIdentifier());
        $this->assertSame($approvedAt, $image->approvedAt());
        $this->assertSame((string) $updaterIdentifier, (string) $image->updaterIdentifier());
        $this->assertSame($updatedAt, $image->updatedAt());
    }

    /**
     * 正常系: ImagePathのsetterが正しく動作すること.
     */
    public function testSetImagePath(): void
    {
        $image = $this->createDummyImage();
        $newImagePath = new ImagePath('/resources/public/images/new.webp');

        $image->setImagePath($newImagePath);

        $this->assertSame((string) $newImagePath, (string) $image->imagePath());
    }

    /**
     * 正常系: ImageUsageのsetterが正しく動作すること.
     */
    public function testSetImageUsage(): void
    {
        $image = $this->createDummyImage();

        $image->setImageUsage(ImageUsage::COVER);

        $this->assertSame(ImageUsage::COVER, $image->imageUsage());
    }

    /**
     * 正常系: displayOrderのsetterが正しく動作すること.
     */
    public function testSetDisplayOrder(): void
    {
        $image = $this->createDummyImage();

        $image->setDisplayOrder(5);

        $this->assertSame(5, $image->displayOrder());
    }

    /**
     * 正常系: sourceUrlのsetterが正しく動作すること.
     */
    public function testSetSourceUrl(): void
    {
        $image = $this->createDummyImage();
        $newSourceUrl = 'https://example.com/new-source';

        $image->setSourceUrl($newSourceUrl);

        $this->assertSame($newSourceUrl, $image->sourceUrl());
    }

    /**
     * 正常系: sourceNameのsetterが正しく動作すること.
     */
    public function testSetSourceName(): void
    {
        $image = $this->createDummyImage();
        $newSourceName = 'New Source Name';

        $image->setSourceName($newSourceName);

        $this->assertSame($newSourceName, $image->sourceName());
    }

    /**
     * 正常系: altTextのsetterが正しく動作すること.
     */
    public function testSetAltText(): void
    {
        $image = $this->createDummyImage();
        $newAltText = 'New alt text for image';

        $image->setAltText($newAltText);

        $this->assertSame($newAltText, $image->altText());
    }

    /**
     * 正常系: hide()が正しく動作すること.
     */
    public function testHide(): void
    {
        $image = $this->createDummyImage();
        $hiddenBy = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->assertFalse($image->isHidden());
        $this->assertNull($image->hiddenBy());
        $this->assertNull($image->hiddenAt());

        $image->hide($hiddenBy);

        $this->assertTrue($image->isHidden());
        $this->assertSame((string) $hiddenBy, (string) $image->hiddenBy());
        $this->assertInstanceOf(DateTimeImmutable::class, $image->hiddenAt());
    }

    /**
     * 正常系: unhide()が正しく動作すること.
     */
    public function testUnhide(): void
    {
        $image = $this->createDummyImage(isHidden: true);

        $this->assertTrue($image->isHidden());
        $this->assertNotNull($image->hiddenBy());
        $this->assertNotNull($image->hiddenAt());

        $image->unhide();

        $this->assertFalse($image->isHidden());
        $this->assertNull($image->hiddenBy());
        $this->assertNull($image->hiddenAt());
    }

    /**
     * 正常系: requestHide後にpendingHideRequestがセットされること.
     */
    public function testRequestHide(): void
    {
        $image = $this->createDummyImage();

        $this->assertSame([], $image->hideRequests());
        $this->assertNull($image->pendingHideRequest());

        $image->requestHide('Test Requester', 'requester@example.com', 'Privacy concern');

        $this->assertCount(1, $image->hideRequests());
        $this->assertInstanceOf(HideRequest::class, $image->pendingHideRequest());
        $this->assertSame('Test Requester', $image->pendingHideRequest()->requesterName());
        $this->assertSame('requester@example.com', $image->pendingHideRequest()->requesterEmail());
        $this->assertSame('Privacy concern', $image->pendingHideRequest()->reason());
        $this->assertSame(ImageHideRequestStatus::PENDING, $image->pendingHideRequest()->status());
    }

    /**
     * 正常系: approveHideRequest後にlatestHideRequestがAPPROVEDかつisHidden=trueになること.
     */
    public function testApproveHideRequest(): void
    {
        $image = $this->createDummyImage();
        $image->requestHide('Test Requester', 'requester@example.com', 'Privacy concern');

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $image->approveHideRequest($reviewerIdentifier, 'Approved for privacy');

        $this->assertNull($image->pendingHideRequest());
        $this->assertSame(ImageHideRequestStatus::APPROVED, $image->latestHideRequest()->status());
        $this->assertSame('Approved for privacy', $image->latestHideRequest()->reviewerComment());
        $this->assertTrue($image->isHidden());
    }

    /**
     * 正常系: rejectHideRequest後にlatestHideRequestがREJECTEDになること.
     */
    public function testRejectHideRequest(): void
    {
        $image = $this->createDummyImage();
        $image->requestHide('Test Requester', 'requester@example.com', 'Privacy concern');

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $image->rejectHideRequest($reviewerIdentifier, 'Not applicable');

        $this->assertNull($image->pendingHideRequest());
        $this->assertSame(ImageHideRequestStatus::REJECTED, $image->latestHideRequest()->status());
        $this->assertSame('Not applicable', $image->latestHideRequest()->reviewerComment());
        $this->assertFalse($image->isHidden());
    }

    /**
     * 異常系: 既にpendingのhideRequestがある場合にrequestHideで例外がスローされること.
     */
    public function testRequestHideThrowsWhenAlreadyPending(): void
    {
        $image = $this->createDummyImage();
        $image->requestHide('First Requester', 'first@example.com', 'First reason');

        $this->expectException(ImageHideRequestAlreadyPendingException::class);
        $image->requestHide('Second Requester', 'second@example.com', 'Second reason');
    }

    /**
     * 正常系: reject済みのhideRequestがある場合にrequestHideで新しいリクエストが作成されること.
     */
    public function testRequestHideAfterRejection(): void
    {
        $image = $this->createDummyImage();
        $image->requestHide('First Requester', 'first@example.com', 'First reason');

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $image->rejectHideRequest($reviewerIdentifier, 'Rejected');

        $image->requestHide('Second Requester', 'second@example.com', 'Second reason');

        $this->assertCount(2, $image->hideRequests());
        $this->assertSame(ImageHideRequestStatus::REJECTED, $image->hideRequests()[0]->status());
        $this->assertSame('Second Requester', $image->pendingHideRequest()->requesterName());
        $this->assertSame(ImageHideRequestStatus::PENDING, $image->pendingHideRequest()->status());
    }

    /**
     * 異常系: hideRequestがnullの場合にapproveHideRequestで例外がスローされること.
     */
    public function testApproveHideRequestThrowsWhenNoHideRequest(): void
    {
        $image = $this->createDummyImage();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(ImageHideRequestNotPendingException::class);
        $image->approveHideRequest($reviewerIdentifier, 'comment');
    }

    /**
     * 異常系: hideRequestがnullの場合にrejectHideRequestで例外がスローされること.
     */
    public function testRejectHideRequestThrowsWhenNoHideRequest(): void
    {
        $image = $this->createDummyImage();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(ImageHideRequestNotPendingException::class);
        $image->rejectHideRequest($reviewerIdentifier, 'comment');
    }

    private function createDummyImage(bool $isHidden = false): Image
    {
        return new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('/resources/public/images/test.webp'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
            $isHidden,
            $isHidden ? new PrincipalIdentifier(StrTestHelper::generateUuid()) : null,
            $isHidden ? new DateTimeImmutable() : null,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            null,
            null,
        );
    }
}
