<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;

class DraftImageTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること（新規作成、publishedImageIdentifierがnull）.
     */
    public function test__constructWithoutPublishedImageIdentifier(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $draftResourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $status = ApprovalStatus::UnderReview;
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $createdAt = new DateTimeImmutable();

        $draftImage = new DraftImage(
            $imageIdentifier,
            null,
            $resourceType,
            $draftResourceIdentifier,
            $editorIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $status,
            $agreedToTermsAt,
            $createdAt,
        );

        $this->assertSame((string) $imageIdentifier, (string) $draftImage->imageIdentifier());
        $this->assertNull($draftImage->publishedImageIdentifier());
        $this->assertSame($resourceType, $draftImage->resourceType());
        $this->assertSame((string) $draftResourceIdentifier, (string) $draftImage->draftResourceIdentifier());
        $this->assertSame((string) $editorIdentifier, (string) $draftImage->editorIdentifier());
        $this->assertSame((string) $imagePath, (string) $draftImage->imagePath());
        $this->assertSame($imageUsage, $draftImage->imageUsage());
        $this->assertSame($displayOrder, $draftImage->displayOrder());
        $this->assertSame($sourceUrl, $draftImage->sourceUrl());
        $this->assertSame($sourceName, $draftImage->sourceName());
        $this->assertSame($altText, $draftImage->altText());
        $this->assertSame($status, $draftImage->status());
        $this->assertSame($agreedToTermsAt, $draftImage->agreedToTermsAt());
        $this->assertSame($createdAt, $draftImage->createdAt());
    }

    /**
     * 正常系: インスタンスが生成されること（編集、publishedImageIdentifierあり）.
     */
    public function test__constructWithPublishedImageIdentifier(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $publishedImageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::SONG;
        $draftResourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/cover.webp');
        $imageUsage = ImageUsage::COVER;
        $displayOrder = 0;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Cover image of song';
        $status = ApprovalStatus::UnderReview;
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $createdAt = new DateTimeImmutable();

        $draftImage = new DraftImage(
            $imageIdentifier,
            $publishedImageIdentifier,
            $resourceType,
            $draftResourceIdentifier,
            $editorIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $status,
            $agreedToTermsAt,
            $createdAt,
        );

        $this->assertSame((string) $publishedImageIdentifier, (string) $draftImage->publishedImageIdentifier());
    }

    /**
     * 正常系: ImagePathのsetterが正しく動作すること.
     */
    public function testSetImagePath(): void
    {
        $draftImage = $this->createDummyDraftImage();
        $newImagePath = new ImagePath('/resources/public/images/new.webp');

        $draftImage->setImagePath($newImagePath);

        $this->assertSame((string) $newImagePath, (string) $draftImage->imagePath());
    }

    /**
     * 正常系: ImageUsageのsetterが正しく動作すること.
     */
    public function testSetImageUsage(): void
    {
        $draftImage = $this->createDummyDraftImage();

        $draftImage->setImageUsage(ImageUsage::ADDITIONAL);

        $this->assertSame(ImageUsage::ADDITIONAL, $draftImage->imageUsage());
    }

    /**
     * 正常系: displayOrderのsetterが正しく動作すること.
     */
    public function testSetDisplayOrder(): void
    {
        $draftImage = $this->createDummyDraftImage();

        $draftImage->setDisplayOrder(3);

        $this->assertSame(3, $draftImage->displayOrder());
    }

    /**
     * 正常系: sourceUrlのsetterが正しく動作すること.
     */
    public function testSetSourceUrl(): void
    {
        $draftImage = $this->createDummyDraftImage();
        $newSourceUrl = 'https://example.com/new-source';

        $draftImage->setSourceUrl($newSourceUrl);

        $this->assertSame($newSourceUrl, $draftImage->sourceUrl());
    }

    /**
     * 正常系: sourceNameのsetterが正しく動作すること.
     */
    public function testSetSourceName(): void
    {
        $draftImage = $this->createDummyDraftImage();
        $newSourceName = 'New Source Name';

        $draftImage->setSourceName($newSourceName);

        $this->assertSame($newSourceName, $draftImage->sourceName());
    }

    /**
     * 正常系: altTextのsetterが正しく動作すること.
     */
    public function testSetAltText(): void
    {
        $draftImage = $this->createDummyDraftImage();
        $newAltText = 'New alt text for image';

        $draftImage->setAltText($newAltText);

        $this->assertSame($newAltText, $draftImage->altText());
    }

    private function createDummyDraftImage(): DraftImage
    {
        return new DraftImage(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            null,
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('/resources/public/images/test.webp'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
            ApprovalStatus::UnderReview,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable(),
        );
    }
}
