<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Infrastructure\Service;

use Application\Models\Wiki\VideoLink as VideoLinkModel;
use Application\Models\Wiki\VideoLinkLike as VideoLinkLikeModel;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Playlist\Application\Service\PlaylistVideoSourceServiceInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItem;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemPosition;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Service\VideoUrlBuilderInterface;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;

readonly class PlaylistVideoSourceService implements PlaylistVideoSourceServiceInterface
{
    public function __construct(private VideoUrlBuilderInterface $videoUrlBuilder)
    {
    }

    /**
     * @param list<VideoLinkIdentifier> $videoLinkIdentifiers
     * @return array<string, PlaylistItem>
     */
    public function resolveMany(array $videoLinkIdentifiers, PrincipalIdentifier $principalIdentifier, PlaylistIdentifier $playlistIdentifier): array
    {
        if ($videoLinkIdentifiers === []) {
            return [];
        }

        $identifiers = array_values(array_unique(array_map(static fn (VideoLinkIdentifier $identifier): string => (string) $identifier, $videoLinkIdentifiers)));
        $videos = VideoLinkModel::query()->whereIn('id', $identifiers)
            ->whereHas('wiki', static fn ($query) => $query->whereNotNull('published_at'))->get()->keyBy('id');
        $likedVideos = VideoLinkLikeModel::query()->whereIn('video_link_id', $identifiers)
            ->where('principal_id', (string) $principalIdentifier)->get(['video_link_id'])->keyBy('video_link_id');

        $items = [];
        foreach ($identifiers as $identifier) {
            $video = $videos->get($identifier);
            if ($video === null) {
                throw new VideoLinkNotFoundException();
            }
            if (! $likedVideos->has($identifier)) {
                throw new DisallowedException();
            }
            $items[$identifier] = $this->createItem($video, $playlistIdentifier);
        }

        return $items;
    }

    private function createItem(VideoLinkModel $video, PlaylistIdentifier $playlistIdentifier): PlaylistItem
    {
        $url = $this->videoUrlBuilder->build($video->url);

        return new PlaylistItem(
            $playlistIdentifier,
            new VideoLinkIdentifier($video->id),
            VideoProvider::from($url->provider()),
            new ExternalVideoId($url->externalVideoId() ?? throw new InvalidArgumentException('A resolved video URL is required.')),
            $url,
            new VideoTitle($video->title),
            $video->thumbnail_url === null ? null : new ThumbnailUrl($video->thumbnail_url),
            $video->duration_seconds === null ? null : new DurationSeconds($video->duration_seconds),
            new PlaylistItemPosition(0),
            $video->thumbnail_path === null ? null : new ImagePath($video->thumbnail_path),
        );
    }
}
