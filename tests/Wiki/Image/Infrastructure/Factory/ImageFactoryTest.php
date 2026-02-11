<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Factory\ImageFactoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Image\Infrastructure\Factory\ImageFactory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ImageFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(ImageFactoryInterface::class);
        $this->assertInstanceOf(ImageFactory::class, $factory);
    }

    /**
     * 正常系: Image Entityが正しく作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $uploaderIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approvedAt = new DateTimeImmutable();

        $factory = $this->app->make(ImageFactoryInterface::class);
        $image = $factory->create(
            $resourceType,
            $resourceIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $uploaderIdentifier,
            $approverIdentifier,
            $approvedAt,
        );

        $this->assertTrue(UuidValidator::isValid((string) $image->imageIdentifier()));
        $this->assertSame($resourceType, $image->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $image->wikiIdentifier());
        $this->assertSame((string) $imagePath, (string) $image->imagePath());
        $this->assertSame($imageUsage, $image->imageUsage());
        $this->assertSame($displayOrder, $image->displayOrder());
        $this->assertSame($sourceUrl, $image->sourceUrl());
        $this->assertSame($sourceName, $image->sourceName());
        $this->assertSame($altText, $image->altText());
        $this->assertSame((string) $uploaderIdentifier, (string) $image->uploaderIdentifier());
        $this->assertSame((string) $approverIdentifier, (string) $image->approverIdentifier());
        $this->assertSame($approvedAt, $image->approvedAt());
        $this->assertNull($image->updaterIdentifier());
        $this->assertNull($image->updatedAt());
    }
}
