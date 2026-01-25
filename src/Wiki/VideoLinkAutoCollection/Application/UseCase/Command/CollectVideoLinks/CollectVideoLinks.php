<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\VideoLinkAutoCollection\Domain\Repository\VideoLinkCollectionStatusRepositoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\Service\YouTubeSearchServiceInterface;

readonly class CollectVideoLinks implements CollectVideoLinksInterface
{
    public function __construct(
        private VideoLinkCollectionStatusRepositoryInterface $collectionStatusRepository,
        private TalentRepositoryInterface $talentRepository,
        private GroupRepositoryInterface $groupRepository,
        private SongRepositoryInterface $songRepository,
        private YouTubeSearchServiceInterface $youTubeSearchService,
        private VideoLinkFactoryInterface $videoLinkFactory,
        private VideoLinkRepositoryInterface $videoLinkRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function process(CollectVideoLinksOutputPort $output): void
    {
        $status = $this->collectionStatusRepository->findNextTargetResource();

        if ($status === null) {
            $this->logger->info('CollectVideoLinks: No target resource found');
            $output->noTargetResource();

            return;
        }

        $oneMonthAgo = new DateTimeImmutable('-1 month');
        if ($status->lastCollectedAt() !== null && $status->lastCollectedAt() > $oneMonthAgo) {
            $this->logger->info('CollectVideoLinks: Resource was collected within the last month', [
                'resource_type' => $status->resourceType()->value,
                'resource_identifier' => (string) $status->resourceIdentifier(),
                'last_collected_at' => $status->lastCollectedAt()->format('Y-m-d H:i:s'),
            ]);
            $output->recentlyCollected(
                $status->resourceType(),
                $status->resourceIdentifier(),
            );

            return;
        }

        $resourceName = match ($status->resourceType()) {
            ResourceType::TALENT => $this->getTalentName($status),
            ResourceType::GROUP => $this->getGroupName($status),
            ResourceType::SONG => $this->getSongName($status),
            default => null,
        };

        if ($resourceName === null) {
            $this->logger->warning('CollectVideoLinks: Resource not found', [
                'resource_type' => $status->resourceType()->value,
                'resource_identifier' => (string) $status->resourceIdentifier(),
            ]);
            $output->resourceNotFound(
                $status->resourceType(),
                $status->resourceIdentifier(),
            );

            return;
        }

        $this->logger->info('CollectVideoLinks: Starting collection', [
            'resource_type' => $status->resourceType()->value,
            'resource_identifier' => (string) $status->resourceIdentifier(),
            'resource_name' => $resourceName,
        ]);

        $videos = $this->youTubeSearchService->searchVideos($resourceName);

        $this->videoLinkRepository->deleteAutoCollectedByResource(
            $status->resourceType(),
            $status->resourceIdentifier(),
        );

        $videoUrls = array_map(static fn ($video) => $video->url(), $videos);
        $existingVideoLinks = $this->videoLinkRepository->findByResourceAndUrls(
            $status->resourceType(),
            $status->resourceIdentifier(),
            $videoUrls,
        );
        $existingUrls = array_map(static fn ($videoLink) => (string) $videoLink->url(), $existingVideoLinks);

        $lastVideoLink = $this->videoLinkRepository->findByResourceWithMaxDisplayOrder(
            $status->resourceType(),
            $status->resourceIdentifier(),
        );
        $maxDisplayOrder = $lastVideoLink?->displayOrder() ?? 0;

        $savedCount = 0;
        foreach ($videos as $video) {
            if (in_array($video->url(), $existingUrls, true)) {
                continue;
            }

            $videoLink = $this->videoLinkFactory->create(
                $status->resourceType(),
                $status->resourceIdentifier(),
                new ExternalContentLink($video->url()),
                $video->videoUsage(),
                $video->title(),
                $maxDisplayOrder + $savedCount + 1,
            );
            $videoLink->setThumbnailUrl($video->thumbnailUrl());
            $videoLink->setPublishedAt($video->publishedAt());

            $this->videoLinkRepository->save($videoLink);
            $savedCount++;
        }

        $status->markCollected(new DateTimeImmutable());
        $this->collectionStatusRepository->save($status);

        $this->logger->info('CollectVideoLinks: Collection completed', [
            'resource_type' => $status->resourceType()->value,
            'resource_identifier' => (string) $status->resourceIdentifier(),
            'collected_count' => count($videos),
        ]);

        $output->success(
            $status->resourceType(),
            $status->resourceIdentifier(),
            count($videos),
        );
    }

    private function getTalentName(VideoLinkCollectionStatus $status): ?string
    {
        $talent = $this->talentRepository->findById(
            new TalentIdentifier((string) $status->resourceIdentifier()),
        );

        return $talent !== null ? (string) $talent->name() : null;
    }

    private function getGroupName(VideoLinkCollectionStatus $status): ?string
    {
        $group = $this->groupRepository->findById(
            new GroupIdentifier((string) $status->resourceIdentifier()),
        );

        return $group !== null ? (string) $group->name() : null;
    }

    private function getSongName(VideoLinkCollectionStatus $status): ?string
    {
        $song = $this->songRepository->findById(
            new SongIdentifier((string) $status->resourceIdentifier()),
        );

        return $song !== null ? (string) $song->name() : null;
    }
}
