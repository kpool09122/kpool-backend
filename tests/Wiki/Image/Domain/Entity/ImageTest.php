<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
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
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $createdAt = new DateTimeImmutable();
        $updatedAt = new DateTimeImmutable();

        $image = new Image(
            $imageIdentifier,
            $resourceType,
            $resourceIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $createdAt,
            $updatedAt,
        );

        $this->assertSame((string) $imageIdentifier, (string) $image->imageIdentifier());
        $this->assertSame($resourceType, $image->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $image->resourceIdentifier());
        $this->assertSame((string) $imagePath, (string) $image->imagePath());
        $this->assertSame($imageUsage, $image->imageUsage());
        $this->assertSame($displayOrder, $image->displayOrder());
        $this->assertSame($createdAt, $image->createdAt());
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

    private function createDummyImage(): Image
    {
        return new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('/resources/public/images/test.webp'),
            ImageUsage::PROFILE,
            1,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );
    }
}
