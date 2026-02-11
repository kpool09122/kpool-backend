<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLinkAutoCollection\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class VideoLinkCollectionStatusTest extends TestCase
{
    /**
     * 正常系: インスタンスを作成できること.
     */
    public function test__construct(): void
    {
        $identifier = new VideoLinkCollectionStatusIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();

        $status = new VideoLinkCollectionStatus(
            $identifier,
            $resourceType,
            $resourceIdentifier,
            null,
            $createdAt,
        );

        $this->assertSame($identifier, $status->identifier());
        $this->assertSame($resourceType, $status->resourceType());
        $this->assertSame($resourceIdentifier, $status->wikiIdentifier());
        $this->assertNull($status->lastCollectedAt());
        $this->assertSame($createdAt, $status->createdAt());
    }

    /**
     * 正常系: lastCollectedAtを指定してインスタンスを作成できること.
     */
    public function testConstructWithLastCollectedAt(): void
    {
        $identifier = new VideoLinkCollectionStatusIdentifier(StrTestHelper::generateUuid());
        $lastCollectedAt = new DateTimeImmutable('2024-01-15T10:30:00Z');
        $createdAt = new DateTimeImmutable('2024-01-01T00:00:00Z');

        $status = new VideoLinkCollectionStatus(
            $identifier,
            ResourceType::GROUP,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            $lastCollectedAt,
            $createdAt,
        );

        $this->assertSame($lastCollectedAt, $status->lastCollectedAt());
    }

    /**
     * 正常系: markCollected()でlastCollectedAtを更新できること.
     */
    public function testMarkCollected(): void
    {
        $identifier = new VideoLinkCollectionStatusIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();

        $status = new VideoLinkCollectionStatus(
            $identifier,
            ResourceType::SONG,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            null,
            $createdAt,
        );

        $this->assertNull($status->lastCollectedAt());

        $collectedAt = new DateTimeImmutable('2024-01-20T15:00:00Z');
        $status->markCollected($collectedAt);

        $this->assertSame($collectedAt, $status->lastCollectedAt());
    }

    /**
     * 正常系: markCollected()を複数回呼び出して更新できること.
     */
    public function testMarkCollectedMultipleTimes(): void
    {
        $identifier = new VideoLinkCollectionStatusIdentifier(StrTestHelper::generateUuid());

        $status = new VideoLinkCollectionStatus(
            $identifier,
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            null,
            new DateTimeImmutable(),
        );

        $firstCollectedAt = new DateTimeImmutable('2024-01-15T10:00:00Z');
        $status->markCollected($firstCollectedAt);
        $this->assertSame($firstCollectedAt, $status->lastCollectedAt());

        $secondCollectedAt = new DateTimeImmutable('2024-01-20T15:00:00Z');
        $status->markCollected($secondCollectedAt);
        $this->assertSame($secondCollectedAt, $status->lastCollectedAt());
    }
}
