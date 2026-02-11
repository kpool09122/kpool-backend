<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\Repository\VideoLinkCollectionStatusRepositoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\Service\YouTubeSearchServiceInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;

readonly class CollectVideoLinks implements CollectVideoLinksInterface
{
    public function __construct(
        private VideoLinkCollectionStatusRepositoryInterface $collectionStatusRepository,
        private WikiRepositoryInterface $wikiRepository,
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
                'wiki_id' => (string) $status->wikiIdentifier(),
                'last_collected_at' => $status->lastCollectedAt()->format('Y-m-d H:i:s'),
            ]);
            $output->recentlyCollected(
                $status->resourceType(),
                $status->wikiIdentifier(),
            );

            return;
        }

        $wiki = $this->wikiRepository->findById(
            $status->wikiIdentifier(),
        );
        $resourceName = $wiki !== null ? (string) $wiki->basic()->name() : null;

        if ($resourceName === null) {
            $this->logger->warning('CollectVideoLinks: Resource not found', [
                'resource_type' => $status->resourceType()->value,
                'wiki_id' => (string) $status->wikiIdentifier(),
            ]);
            $output->resourceNotFound(
                $status->resourceType(),
                $status->wikiIdentifier(),
            );

            return;
        }

        $this->logger->info('CollectVideoLinks: Starting collection', [
            'resource_type' => $status->resourceType()->value,
            'wiki_id' => (string) $status->wikiIdentifier(),
            'resource_name' => $resourceName,
        ]);

        $videos = $this->youTubeSearchService->searchVideos($resourceName);

        $this->videoLinkRepository->deleteAutoCollectedByResource(
            $status->resourceType(),
            $status->wikiIdentifier(),
        );

        $videoUrls = array_map(static fn ($video) => $video->url(), $videos);
        $existingVideoLinks = $this->videoLinkRepository->findByResourceAndUrls(
            $status->resourceType(),
            $status->wikiIdentifier(),
            $videoUrls,
        );
        $existingUrls = array_map(static fn ($videoLink) => (string) $videoLink->url(), $existingVideoLinks);

        $lastVideoLink = $this->videoLinkRepository->findByResourceWithMaxDisplayOrder(
            $status->resourceType(),
            $status->wikiIdentifier(),
        );
        $maxDisplayOrder = $lastVideoLink?->displayOrder() ?? 0;

        $savedCount = 0;
        foreach ($videos as $video) {
            if (in_array($video->url(), $existingUrls, true)) {
                continue;
            }

            $videoLink = $this->videoLinkFactory->create(
                $status->resourceType(),
                $status->wikiIdentifier(),
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
            'wiki_id' => (string) $status->wikiIdentifier(),
            'collected_count' => count($videos),
        ]);

        $output->success(
            $status->resourceType(),
            $status->wikiIdentifier(),
            count($videos),
        );
    }
}
