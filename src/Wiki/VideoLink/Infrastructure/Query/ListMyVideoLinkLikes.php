<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Query;

use Application\Models\Wiki\VideoLinkLike as VideoLinkLikeModel;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesOutputPort;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

readonly class ListMyVideoLinkLikes implements ListMyVideoLinkLikesInterface
{
    public function process(ListMyVideoLinkLikesInputPort $input, ListMyVideoLinkLikesOutputPort $output): void
    {
        $identifiers = VideoLinkLikeModel::query()->select('video_link_id')
            ->where('principal_id', (string) $input->principalIdentifier())
            ->whereIn('video_link_id', array_map(static fn (VideoLinkIdentifier $id): string => (string) $id, $input->videoLinkIdentifiers()))
            ->orderBy('video_link_id')->get()
            ->map(static fn (VideoLinkLikeModel $like): string => $like->video_link_id)->all();
        $output->setLikedVideoLinkIdentifiers(array_values($identifiers));
    }
}
