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

        $resourceSnapshotIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());

        $factory = $this->app->make(ImageSnapshotFactoryInterface::class);
        $snapshot = $factory->create($image, $resourceSnapshotIdentifier);

        $this->assertTrue(UuidValidator::isValid((string) $snapshot->snapshotIdentifier()));
        $this->assertSame((string) $imageIdentifier, (string) $snapshot->imageIdentifier());
        $this->assertSame((string) $resourceSnapshotIdentifier, (string) $snapshot->resourceSnapshotIdentifier());
        $this->assertSame((string) $imagePath, (string) $snapshot->imagePath());
        $this->assertSame($imageUsage, $snapshot->imageUsage());
        $this->assertSame($displayOrder, $snapshot->displayOrder());
    }
}
