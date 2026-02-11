<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLinkAutoCollection\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\VideoLinkAutoCollection\Domain\Repository\VideoLinkCollectionStatusRepositoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateVideoLinkCollectionStatus;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class VideoLinkCollectionStatusRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したリソースの収集状態が取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResource(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();

        CreateVideoLinkCollectionStatus::create($statusId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'last_collected_at' => '2024-01-15 10:30:00',
        ]);

        $repository = $this->app->make(VideoLinkCollectionStatusRepositoryInterface::class);
        $status = $repository->findByResource(
            ResourceType::TALENT,
            new WikiIdentifier($wikiId),
        );

        $this->assertInstanceOf(VideoLinkCollectionStatus::class, $status);
        $this->assertSame($statusId, (string) $status->identifier());
        $this->assertSame(ResourceType::TALENT, $status->resourceType());
        $this->assertSame($wikiId, (string) $status->wikiIdentifier());
        $this->assertInstanceOf(DateTimeImmutable::class, $status->lastCollectedAt());
    }

    /**
     * 正常系：指定したリソースの収集状態が存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceWhenNotExist(): void
    {
        $repository = $this->app->make(VideoLinkCollectionStatusRepositoryInterface::class);
        $status = $repository->findByResource(
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertNull($status);
    }

    /**
     * 正常系：未収集のリソースが優先して取得されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindNextTargetResourceReturnsUncollectedFirst(): void
    {
        $uncollectedId = StrTestHelper::generateUuid();
        $collectedId = StrTestHelper::generateUuid();

        CreateVideoLinkCollectionStatus::create($collectedId, [
            'resource_type' => ResourceType::TALENT->value,
            'last_collected_at' => '2024-01-15 10:30:00',
        ]);

        CreateVideoLinkCollectionStatus::create($uncollectedId, [
            'resource_type' => ResourceType::GROUP->value,
            'last_collected_at' => null,
        ]);

        $repository = $this->app->make(VideoLinkCollectionStatusRepositoryInterface::class);
        $status = $repository->findNextTargetResource();

        $this->assertInstanceOf(VideoLinkCollectionStatus::class, $status);
        $this->assertSame($uncollectedId, (string) $status->identifier());
        $this->assertNull($status->lastCollectedAt());
    }

    /**
     * 正常系：全て収集済みの場合、最も古い収集日のリソースが取得されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindNextTargetResourceReturnsOldestCollected(): void
    {
        $oldestId = StrTestHelper::generateUuid();
        $newestId = StrTestHelper::generateUuid();

        CreateVideoLinkCollectionStatus::create($oldestId, [
            'resource_type' => ResourceType::TALENT->value,
            'last_collected_at' => '2024-01-10 10:00:00',
        ]);

        CreateVideoLinkCollectionStatus::create($newestId, [
            'resource_type' => ResourceType::GROUP->value,
            'last_collected_at' => '2024-01-15 15:00:00',
        ]);

        $repository = $this->app->make(VideoLinkCollectionStatusRepositoryInterface::class);
        $status = $repository->findNextTargetResource();

        $this->assertInstanceOf(VideoLinkCollectionStatus::class, $status);
        $this->assertSame($oldestId, (string) $status->identifier());
    }

    /**
     * 正常系：収集対象が存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindNextTargetResourceReturnsNullWhenEmpty(): void
    {
        $repository = $this->app->make(VideoLinkCollectionStatusRepositoryInterface::class);
        $status = $repository->findNextTargetResource();

        $this->assertNull($status);
    }

    /**
     * 正常系：新規の収集状態を保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveNewStatus(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();

        $status = new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($statusId),
            ResourceType::SONG,
            new WikiIdentifier($wikiId),
            null,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(VideoLinkCollectionStatusRepositoryInterface::class);
        $repository->save($status);

        $this->assertDatabaseHas('video_link_collection_statuses', [
            'id' => $statusId,
            'resource_type' => ResourceType::SONG->value,
            'wiki_id' => $wikiId,
            'last_collected_at' => null,
        ]);
    }

    /**
     * 正常系：既存の収集状態を更新できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdateStatus(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();

        CreateVideoLinkCollectionStatus::create($statusId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'last_collected_at' => null,
        ]);

        $repository = $this->app->make(VideoLinkCollectionStatusRepositoryInterface::class);
        $status = $repository->findByResource(
            ResourceType::TALENT,
            new WikiIdentifier($wikiId),
        );

        $collectedAt = new DateTimeImmutable('2024-01-20 15:00:00');
        $status->markCollected($collectedAt);

        $repository->save($status);

        $this->assertDatabaseHas('video_link_collection_statuses', [
            'id' => $statusId,
            'last_collected_at' => '2024-01-20 15:00:00',
        ]);
    }
}
