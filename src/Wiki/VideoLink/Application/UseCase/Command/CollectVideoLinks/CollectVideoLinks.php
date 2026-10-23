<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CollectVideoLinks;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\VideoLink\Application\Service\VideoSearchKeywordBuilder;
use Source\Wiki\VideoLink\Domain\Exception\VideoLinkCollectionFailedException;
use Source\Wiki\VideoLink\Domain\Exception\VideoSearchFailedException;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkCollectionStatusRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Service\YouTubeSearchServiceInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Throwable;

readonly class CollectVideoLinks implements CollectVideoLinksInterface
{
    public function __construct(
        private VideoLinkCollectionStatusRepositoryInterface $videoLinkCollectionStatusRepository,
        private WikiRepositoryInterface $wikiRepository,
        private YouTubeSearchServiceInterface $youTubeSearchService,
        private VideoLinkRepositoryInterface $videoLinkRepository,
        private VideoLinkFactoryInterface $videoLinkFactory,
        private VideoSearchKeywordBuilder $videoSearchKeywordBuilder,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws VideoSearchFailedException
     * @throws VideoLinkCollectionFailedException
     */
    public function process(CollectVideoLinksOutputPort $output): void
    {
        $status = $this->videoLinkCollectionStatusRepository->findNextTargetResource();

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
        if ($wiki === null) {
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

        $keyword = $this->videoSearchKeywordBuilder->build($wiki);

        $this->logger->info('CollectVideoLinks: Starting collection', [
            'resource_type' => $status->resourceType()->value,
            'wiki_id' => (string) $status->wikiIdentifier(),
            'resource_name' => (string) $wiki->basic()->name(),
            'search_keyword' => $keyword,
        ]);

        $videos = $this->youTubeSearchService->searchVideos($keyword);

        try {
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

            $updatedStatus = clone $status;
            $updatedStatus->markCollected(new DateTimeImmutable());
            $this->videoLinkCollectionStatusRepository->save($updatedStatus);
        } catch (Throwable $e) {
            throw new VideoLinkCollectionFailedException($e);
        }

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
