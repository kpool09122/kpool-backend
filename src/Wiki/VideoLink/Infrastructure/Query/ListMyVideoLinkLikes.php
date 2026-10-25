<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Query;

use Application\Models\Wiki\VideoLinkLike as VideoLinkLikeModel;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Query\LikedVideoLinkReadModel;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesOutputPort;

readonly class ListMyVideoLinkLikes implements ListMyVideoLinkLikesInterface
{
    public function process(ListMyVideoLinkLikesInputPort $input, ListMyVideoLinkLikesOutputPort $output): void
    {
        $likes = VideoLinkLikeModel::query()
            ->where('wiki_video_link_likes.principal_id', (string) $input->principalIdentifier());
        $requestedIds = $input->videoLinkIdentifiers();
        $identifiers = [];
        if ($requestedIds !== null) {
            $likes->whereIn('wiki_video_link_likes.video_link_id', array_map(static fn (VideoLinkIdentifier $id): string => (string) $id, $requestedIds));
            $identifiers = (clone $likes)->select('video_link_id')->orderBy('video_link_id')->get()
                ->map(static fn (VideoLinkLikeModel $like): string => $like->video_link_id)->all();
        }
        $videosQuery = (clone $likes)
            ->whereHas('videoLink.wiki', static fn ($query) => $query->whereNotNull('published_at'))
            ->with(['videoLink.wiki']);
        $pagination = null;
        if ($requestedIds === null) {
            $pagination = ['currentPage' => $input->page(), 'perPage' => $input->perPage(), 'total' => (clone $videosQuery)->count()];
            $videosQuery->orderByDesc('wiki_video_link_likes.created_at')->orderBy('wiki_video_link_likes.video_link_id')
                ->forPage($input->page(), $input->perPage());
        } else {
            $videosQuery->orderBy('wiki_video_link_likes.video_link_id');
        }
        $videos = $videosQuery->get()->map(static function (VideoLinkLikeModel $like): LikedVideoLinkReadModel {
            $video = $like->videoLink;
            assert($video !== null);
            $wiki = $video->wiki;
            assert($wiki !== null);

            return new LikedVideoLinkReadModel(
                $like->video_link_id,
                $video->url,
                $video->title,
                $video->thumbnail_url,
                $video->duration_seconds,
                $wiki->language,
                $wiki->resource_type,
                $wiki->slug,
                $like->created_at->toAtomString(),
            );
        })->all();
        if ($requestedIds === null) {
            $identifiers = array_map(static fn (LikedVideoLinkReadModel $video): string => $video->videoLinkIdentifier, $videos);
        }
        $output->output(array_values($identifiers), array_values($videos), $pagination);
    }
}
