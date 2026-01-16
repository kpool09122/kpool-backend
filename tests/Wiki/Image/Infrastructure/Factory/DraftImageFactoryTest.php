<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Factory\DraftImageFactoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Image\Infrastructure\Factory\DraftImageFactory;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
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
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $factory = $this->app->make(DraftImageFactoryInterface::class);
        $draftImage = $factory->create(
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
            $agreedToTermsAt,
        );

        $this->assertTrue(UuidValidator::isValid((string) $draftImage->imageIdentifier()));
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
        $this->assertSame(ApprovalStatus::UnderReview, $draftImage->status());
        $this->assertSame($agreedToTermsAt, $draftImage->agreedToTermsAt());
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
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Cover image of song';
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $factory = $this->app->make(DraftImageFactoryInterface::class);
        $draftImage = $factory->create(
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
            $agreedToTermsAt,
        );

        $this->assertSame((string) $publishedImageIdentifier, (string) $draftImage->publishedImageIdentifier());
    }
}
