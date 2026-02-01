<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Factory\ImageSnapshotFactoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Image\Infrastructure\Factory\ImageSnapshotFactory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ImageSnapshotFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(ImageSnapshotFactoryInterface::class);
        $this->assertInstanceOf(ImageSnapshotFactory::class, $factory);
    }

    /**
     * 正常系: ImageSnapshot Entityが正しく作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
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
            false,
            null,
            null,
            $uploaderIdentifier,
            $uploadedAt,
            $approverIdentifier,
            $approvedAt,
            $updaterIdentifier,
            $updatedAt,
        );

        $resourceSnapshotIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());

        $factory = $this->app->make(ImageSnapshotFactoryInterface::class);
        $snapshot = $factory->create($image, $resourceSnapshotIdentifier);

        $this->assertTrue(UuidValidator::isValid((string) $snapshot->snapshotIdentifier()));
        $this->assertSame((string) $imageIdentifier, (string) $snapshot->imageIdentifier());
        $this->assertSame((string) $resourceSnapshotIdentifier, (string) $snapshot->resourceSnapshotIdentifier());
        $this->assertSame((string) $imagePath, (string) $snapshot->imagePath());
        $this->assertSame($imageUsage, $snapshot->imageUsage());
        $this->assertSame($displayOrder, $snapshot->displayOrder());
        $this->assertSame($sourceUrl, $snapshot->sourceUrl());
        $this->assertSame($sourceName, $snapshot->sourceName());
        $this->assertSame($altText, $snapshot->altText());
        $this->assertSame((string) $uploaderIdentifier, (string) $snapshot->uploaderIdentifier());
        $this->assertSame($uploadedAt, $snapshot->uploadedAt());
        $this->assertSame((string) $approverIdentifier, (string) $snapshot->approverIdentifier());
        $this->assertSame($approvedAt, $snapshot->approvedAt());
        $this->assertSame((string) $updaterIdentifier, (string) $snapshot->updaterIdentifier());
        $this->assertSame($updatedAt, $snapshot->updatedAt());
    }
}
