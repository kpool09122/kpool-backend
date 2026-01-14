<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
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
            new DateTimeImmutable(),
        );
    }
}
