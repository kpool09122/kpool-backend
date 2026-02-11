<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks;

use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;

readonly class SaveVideoLinks implements SaveVideoLinksInterface
{
    public function __construct(
        private VideoLinkFactoryInterface $videoLinkFactory,
        private VideoLinkRepositoryInterface $videoLinkRepository,
    ) {
    }

    public function process(SaveVideoLinksInputPort $input): void
    {
        // 既存のVideoLinksを削除
        $this->videoLinkRepository->deleteByResource(
            $input->resourceType(),
            $input->wikiIdentifier(),
        );

        // 新しいVideoLinksを保存
        foreach ($input->videoLinks() as $videoLinkData) {
            $videoLink = $this->videoLinkFactory->create(
                $input->resourceType(),
                $input->wikiIdentifier(),
                $videoLinkData->url,
                $videoLinkData->videoUsage,
                $videoLinkData->title,
                $videoLinkData->displayOrder,
            );
            $videoLink->setThumbnailUrl($videoLinkData->thumbnailUrl);
            $videoLink->setPublishedAt($videoLinkData->publishedAt);

            $this->videoLinkRepository->save($videoLink);
        }
    }
}
