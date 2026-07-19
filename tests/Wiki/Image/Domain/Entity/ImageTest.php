<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestAlreadyPendingException;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestNotPendingException;
use Source\Wiki\Image\Domain\ValueObject\DeletionRequest;
use Source\Wiki\Image\Domain\ValueObject\RightsConfirmationAgreed;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
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
        $resourceIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $isHidden = true;
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
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $isHidden,
            $hiddenAt,
            $uploaderIdentifier,
            $uploadedAt,
            $approverIdentifier,
            $approvedAt,
            $updaterIdentifier,
            $updatedAt,
            new RightsConfirmationAgreed(true),
        );

        $this->assertSame((string) $imageIdentifier, (string) $image->imageIdentifier());
        $this->assertSame($resourceType, $image->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $image->translationSetIdentifier());
        $this->assertSame((string) $imagePath, (string) $image->imagePath());
        $this->assertSame($displayOrder, $image->displayOrder());
        $this->assertSame($sourceUrl, $image->sourceUrl());
        $this->assertSame($sourceName, $image->sourceName());
        $this->assertSame($altText, $image->altText());
        $this->assertSame($isHidden, $image->isHidden());
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

        $this->assertFalse($image->isHidden());
        $this->assertNull($image->hiddenAt());

        $image->hide();

        $this->assertTrue($image->isHidden());
        $this->assertInstanceOf(DateTimeImmutable::class, $image->hiddenAt());
    }

    /**
     * 正常系: unhide()が正しく動作すること.
     */
    public function testUnhide(): void
    {
        $image = $this->createDummyImage(isHidden: true);

        $this->assertTrue($image->isHidden());
        $this->assertNotNull($image->hiddenAt());

        $image->unhide();

        $this->assertFalse($image->isHidden());
        $this->assertNull($image->hiddenAt());
    }

    /**
     * 正常系: requestDeletion後にpendingDeletionRequestがセットされること.
     */
    public function testRequestHide(): void
    {
        $image = $this->createDummyImage();

        $this->assertSame([], $image->deletionRequests());
        $this->assertNull($image->pendingDeletionRequest());

        $image->requestDeletion('Test Requester', 'requester@example.com', 'Privacy concern');

        $this->assertCount(1, $image->deletionRequests());
        $this->assertInstanceOf(DeletionRequest::class, $image->pendingDeletionRequest());
        $this->assertSame('Test Requester', $image->pendingDeletionRequest()->requesterName());
        $this->assertSame('requester@example.com', $image->pendingDeletionRequest()->requesterEmail());
        $this->assertSame('Privacy concern', $image->pendingDeletionRequest()->reason());
        $this->assertNull($image->pendingDeletionRequest()->reviewedAt());
    }

    /**
     * 正常系: approveDeletionRequest後にレビュー情報が記録され、isHidden=trueになること.
     */
    public function testApproveDeletionRequest(): void
    {
        $image = $this->createDummyImage();
        $image->requestDeletion('Test Requester', 'requester@example.com', 'Privacy concern');

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $image->approveDeletionRequest($reviewerIdentifier, 'Approved for privacy');

        $this->assertNull($image->pendingDeletionRequest());
        $this->assertSame((string) $reviewerIdentifier, (string) $image->latestDeletionRequest()->reviewerIdentifier());
        $this->assertInstanceOf(DateTimeImmutable::class, $image->latestDeletionRequest()->reviewedAt());
        $this->assertSame('Approved for privacy', $image->latestDeletionRequest()->reviewerComment());
        $this->assertTrue($image->isHidden());
    }

    /**
     * 正常系: rejectDeletionRequest後にレビュー情報が記録され、再掲載されること.
     */
    public function testRejectDeletionRequest(): void
    {
        $image = $this->createDummyImage();
        $image->requestDeletion('Test Requester', 'requester@example.com', 'Privacy concern');

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $image->rejectDeletionRequest($reviewerIdentifier, 'Not applicable');

        $this->assertNull($image->pendingDeletionRequest());
        $this->assertSame((string) $reviewerIdentifier, (string) $image->latestDeletionRequest()->reviewerIdentifier());
        $this->assertInstanceOf(DateTimeImmutable::class, $image->latestDeletionRequest()->reviewedAt());
        $this->assertSame('Not applicable', $image->latestDeletionRequest()->reviewerComment());
        $this->assertFalse($image->isHidden());
    }

    /**
     * 異常系: 既にpendingのdeletionRequestがある場合にrequestDeletionで例外がスローされること.
     */
    public function testRequestHideThrowsWhenAlreadyPending(): void
    {
        $image = $this->createDummyImage();
        $image->requestDeletion('First Requester', 'first@example.com', 'First reason');

        $this->expectException(ImageDeletionRequestAlreadyPendingException::class);
        $image->requestDeletion('Second Requester', 'second@example.com', 'Second reason');
    }

    /**
     * 正常系: reject済みのdeletionRequestがある場合にrequestDeletionで新しいリクエストが作成されること.
     */
    public function testRequestHideAfterRejection(): void
    {
        $image = $this->createDummyImage();
        $image->requestDeletion('First Requester', 'first@example.com', 'First reason');

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $image->rejectDeletionRequest($reviewerIdentifier, 'Rejected');

        $image->requestDeletion('Second Requester', 'second@example.com', 'Second reason');

        $this->assertCount(2, $image->deletionRequests());
        $this->assertInstanceOf(DateTimeImmutable::class, $image->deletionRequests()[0]->reviewedAt());
        $this->assertSame('Second Requester', $image->pendingDeletionRequest()->requesterName());
        $this->assertNull($image->pendingDeletionRequest()->reviewedAt());
    }

    /**
     * 異常系: deletionRequestがnullの場合にapproveDeletionRequestで例外がスローされること.
     */
    public function testApproveDeletionRequestThrowsWhenNoDeletionRequest(): void
    {
        $image = $this->createDummyImage();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(ImageDeletionRequestNotPendingException::class);
        $image->approveDeletionRequest($reviewerIdentifier, 'comment');
    }

    /**
     * 異常系: deletionRequestがnullの場合にrejectDeletionRequestで例外がスローされること.
     */
    public function testRejectDeletionRequestThrowsWhenNoDeletionRequest(): void
    {
        $image = $this->createDummyImage();
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(ImageDeletionRequestNotPendingException::class);
        $image->rejectDeletionRequest($reviewerIdentifier, 'comment');
    }

    private function createDummyImage(bool $isHidden = false): Image
    {
        return new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('/resources/public/images/test.webp'),
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
            $isHidden,
            $isHidden ? new DateTimeImmutable() : null,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            null,
            null,
            new RightsConfirmationAgreed(true),
        );
    }
}
