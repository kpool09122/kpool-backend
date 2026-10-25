<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Query;

use Application\Models\Wiki\VideoLinkLike as VideoLinkLikeModel;
use Source\Shared\Domain\Support\TypedValue;
use Source\Wiki\VideoLink\Application\UseCase\Query\LikedVideoLinkReadModel;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesOutputPort;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

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
        $videosQuery = $likes
            ->join('wiki_video_links', 'wiki_video_links.id', '=', 'wiki_video_link_likes.video_link_id')
            ->join('wikis', 'wikis.id', '=', 'wiki_video_links.wiki_id')
            ->whereNotNull('wikis.published_at')
            ->select('wiki_video_link_likes.*', 'wiki_video_links.url', 'wiki_video_links.title', 'wiki_video_links.thumbnail_url', 'wiki_video_links.duration_seconds', 'wikis.language', 'wikis.resource_type', 'wikis.slug');
        $pagination = null;
        if ($requestedIds === null) {
            $pagination = ['currentPage' => $input->page(), 'perPage' => $input->perPage(), 'total' => (clone $videosQuery)->count()];
            $videosQuery->orderByDesc('wiki_video_link_likes.created_at')->orderBy('wiki_video_link_likes.video_link_id')
                ->forPage($input->page(), $input->perPage());
        } else {
            $videosQuery->orderBy('wiki_video_link_likes.video_link_id');
        }
        $videos = $videosQuery->get()->map(static fn (VideoLinkLikeModel $like): LikedVideoLinkReadModel => new LikedVideoLinkReadModel(
            $like->video_link_id,
            TypedValue::string($like->getAttribute('url')),
            TypedValue::string($like->getAttribute('title')),
            TypedValue::nullableString($like->getAttribute('thumbnail_url')),
            TypedValue::nullableInt($like->getAttribute('duration_seconds')),
            TypedValue::string($like->getAttribute('language')),
            TypedValue::string($like->getAttribute('resource_type')),
            TypedValue::string($like->getAttribute('slug')),
            $like->created_at->toAtomString(),
        ))->all();
        if ($requestedIds === null) {
            $identifiers = array_map(static fn (LikedVideoLinkReadModel $video): string => $video->videoLinkIdentifier, $videos);
        }
        $output->output(array_values($identifiers), array_values($videos), $pagination);
    }
}
