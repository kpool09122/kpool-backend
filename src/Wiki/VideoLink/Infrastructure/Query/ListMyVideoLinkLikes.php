<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Query;

use Application\Models\Wiki\Playlist as PlaylistModel;
use Application\Models\Wiki\VideoLinkLike as VideoLinkLikeModel;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\Service\VideoUrlBuilderInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Query\LikedVideoLinkReadModel;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesOutputPort;

readonly class ListMyVideoLinkLikes implements ListMyVideoLinkLikesInterface
{
    public function __construct(private VideoUrlBuilderInterface $videoUrlBuilder)
    {
    }

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
        $likesOnPage = $videosQuery->get();
        $memberships = $this->playlistMemberships(array_values($likesOnPage->map(static fn (VideoLinkLikeModel $like): string => $like->videoLink->url ?? '')->all()), $input->principalIdentifier());
        $videos = $likesOnPage->map(function (VideoLinkLikeModel $like) use ($memberships): LikedVideoLinkReadModel {
            $video = $like->videoLink;
            assert($video !== null);
            $wiki = $video->wiki;
            assert($wiki !== null);

            return new LikedVideoLinkReadModel(
                $like->video_link_id,
                $this->videoUrlBuilder->build($video->url),
                $video->title,
                $video->thumbnail_path !== null ? ImageUrl::fromPath($video->thumbnail_path) : $video->thumbnail_url,
                $video->duration_seconds,
                $wiki->language,
                $wiki->resource_type,
                $wiki->slug,
                $like->created_at->toAtomString(),
                $memberships[$video->url] ?? [],
                $like->is_official_owner,
            );
        })->all();
        if ($requestedIds === null) {
            $identifiers = array_map(static fn (LikedVideoLinkReadModel $video): string => $video->videoLinkIdentifier, $videos);
        }
        $output->output(array_values($identifiers), array_values($videos), $pagination);
    }

    /**
     * @param list<string> $urls
     * @return array<string, list<array{playlistIdentifier:string,name:string}>>
     */
    private function playlistMemberships(array $urls, PrincipalIdentifier $principalIdentifier): array
    {
        $videoIds = [];
        foreach (array_unique($urls) as $url) {
            try {
                $identity = $this->videoUrlBuilder->build($url);
                $videoId = $identity->externalVideoId() ?? throw new InvalidArgumentException('A resolved video URL is required.');
                $videoIds[$url] = $identity->provider() . ':' . $videoId;
            } catch (InvalidArgumentException) {
                // Unsupported historical URLs have no playlist identity.
            }
        }
        if ($videoIds === []) {
            return [];
        }
        $playlists = PlaylistModel::query()
            ->where('principal_id', (string) $principalIdentifier)
            ->with(['items' => static fn ($query) => $query->whereIn('external_video_id', array_map(static fn (string $identity): string => explode(':', $identity, 2)[1], array_values($videoIds)))])
            ->orderBy('created_at')->orderBy('id')->get();
        $byVideoId = [];
        foreach ($playlists as $playlist) {
            foreach ($playlist->items as $item) {
                $byVideoId[$item->provider . ':' . $item->external_video_id][] = ['playlistIdentifier' => $playlist->id, 'name' => $playlist->name];
            }
        }
        $memberships = [];
        foreach ($videoIds as $url => $videoId) {
            $memberships[$url] = $byVideoId[$videoId] ?? [];
        }

        return $memberships;
    }
}
