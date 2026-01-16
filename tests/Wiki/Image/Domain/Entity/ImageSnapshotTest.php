<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\ImageSnapshot;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Tests\Helper\StrTestHelper;

class ImageSnapshotTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $snapshotIdentifier = new ImageSnapshotIdentifier(StrTestHelper::generateUuid());
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceSnapshotIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $createdAt = new DateTimeImmutable();

        $imageSnapshot = new ImageSnapshot(
            $snapshotIdentifier,
            $imageIdentifier,
            $resourceSnapshotIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $createdAt,
        );

        $this->assertSame((string) $snapshotIdentifier, (string) $imageSnapshot->snapshotIdentifier());
        $this->assertSame((string) $imageIdentifier, (string) $imageSnapshot->imageIdentifier());
        $this->assertSame((string) $resourceSnapshotIdentifier, (string) $imageSnapshot->resourceSnapshotIdentifier());
        $this->assertSame((string) $imagePath, (string) $imageSnapshot->imagePath());
        $this->assertSame($imageUsage, $imageSnapshot->imageUsage());
        $this->assertSame($displayOrder, $imageSnapshot->displayOrder());
        $this->assertSame($sourceUrl, $imageSnapshot->sourceUrl());
        $this->assertSame($sourceName, $imageSnapshot->sourceName());
        $this->assertSame($altText, $imageSnapshot->altText());
        $this->assertSame($createdAt, $imageSnapshot->createdAt());
    }
}
