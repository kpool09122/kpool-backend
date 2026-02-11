<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\UploadImage;

use DateTimeImmutable;
use Source\Wiki\Image\Application\UseCase\Command\UploadImage\UploadImageInput;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UploadImageInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $publishedImageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $draftResourceIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $input = new UploadImageInput(
            $principalIdentifier,
            $publishedImageIdentifier,
            $resourceType,
            $draftResourceIdentifier,
            $base64EncodedImage,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $agreedToTermsAt,
        );

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame((string) $publishedImageIdentifier, (string) $input->publishedImageIdentifier());
        $this->assertSame($resourceType, $input->resourceType());
        $this->assertSame((string) $draftResourceIdentifier, (string) $input->wikiIdentifier());
        $this->assertSame($base64EncodedImage, $input->base64EncodedImage());
        $this->assertSame($imageUsage, $input->imageUsage());
        $this->assertSame($displayOrder, $input->displayOrder());
        $this->assertSame($sourceUrl, $input->sourceUrl());
        $this->assertSame($sourceName, $input->sourceName());
        $this->assertSame($altText, $input->altText());
        $this->assertSame($agreedToTermsAt, $input->agreedToTermsAt());
    }

    /**
     * 正常系: publishedImageIdentifierがnullの場合、nullが返されること
     *
     * @return void
     */
    public function test__constructWithNullPublishedImageIdentifier(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $draftResourceIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $input = new UploadImageInput(
            $principalIdentifier,
            null,
            $resourceType,
            $draftResourceIdentifier,
            $base64EncodedImage,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $agreedToTermsAt,
        );

        $this->assertNull($input->publishedImageIdentifier());
    }
}
