<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Factory\ImageFactoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Image\Infrastructure\Factory\ImageFactory;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
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
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';

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
        );

        $this->assertTrue(UuidValidator::isValid((string) $image->imageIdentifier()));
        $this->assertSame($resourceType, $image->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $image->resourceIdentifier());
        $this->assertSame((string) $imagePath, (string) $image->imagePath());
        $this->assertSame($imageUsage, $image->imageUsage());
        $this->assertSame($displayOrder, $image->displayOrder());
        $this->assertSame($sourceUrl, $image->sourceUrl());
        $this->assertSame($sourceName, $image->sourceName());
        $this->assertSame($altText, $image->altText());
    }
}
