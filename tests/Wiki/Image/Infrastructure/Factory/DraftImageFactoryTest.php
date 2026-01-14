<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Factory\DraftImageFactoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Image\Infrastructure\Factory\DraftImageFactory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftImageFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(DraftImageFactoryInterface::class);
        $this->assertInstanceOf(DraftImageFactory::class, $factory);
    }

    /**
     * 正常系: 新規作成（publishedImageIdentifierがnull）でDraftImage Entityが正しく作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateNew(): void
    {
        $resourceType = ResourceType::TALENT;
        $draftResourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;

        $factory = $this->app->make(DraftImageFactoryInterface::class);
        $draftImage = $factory->create(
            null,
            $resourceType,
            $draftResourceIdentifier,
            $editorIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
        );

        $this->assertTrue(UuidValidator::isValid((string) $draftImage->imageIdentifier()));
        $this->assertNull($draftImage->publishedImageIdentifier());
        $this->assertSame($resourceType, $draftImage->resourceType());
        $this->assertSame((string) $draftResourceIdentifier, (string) $draftImage->draftResourceIdentifier());
        $this->assertSame((string) $editorIdentifier, (string) $draftImage->editorIdentifier());
        $this->assertSame((string) $imagePath, (string) $draftImage->imagePath());
        $this->assertSame($imageUsage, $draftImage->imageUsage());
        $this->assertSame($displayOrder, $draftImage->displayOrder());
    }

    /**
     * 正常系: 編集（publishedImageIdentifierあり）でDraftImage Entityが正しく作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateEdit(): void
    {
        $publishedImageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::SONG;
        $draftResourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/cover.webp');
        $imageUsage = ImageUsage::COVER;
        $displayOrder = 0;

        $factory = $this->app->make(DraftImageFactoryInterface::class);
        $draftImage = $factory->create(
            $publishedImageIdentifier,
            $resourceType,
            $draftResourceIdentifier,
            $editorIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
        );

        $this->assertSame((string) $publishedImageIdentifier, (string) $draftImage->publishedImageIdentifier());
    }
}
