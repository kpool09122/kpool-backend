<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Infrastructure\Repository;

use Application\Models\Wiki\Playlist as PlaylistModel;
use Application\Models\Wiki\PlaylistItem as PlaylistItemModel;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Playlist\Domain\Entity\Playlist;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItem;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemPosition;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Application\Service\VideoThumbnailServiceInterface;
use Source\Wiki\Shared\Domain\Service\VideoUrlBuilderInterface;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;

readonly class PlaylistRepository implements PlaylistRepositoryInterface
{
    public function __construct(
        private VideoThumbnailServiceInterface $videoThumbnailService,
        private VideoUrlBuilderInterface $videoUrlBuilder,
    ) {
    }

    public function countByPrincipal(PrincipalIdentifier $principalIdentifier): int
    {
        return PlaylistModel::query()->where('principal_id', (string) $principalIdentifier)->count();
    }

    public function findByIdAndPrincipal(PlaylistIdentifier $playlistIdentifier, PrincipalIdentifier $principalIdentifier): ?Playlist
    {
        $model = PlaylistModel::query()->where('id', (string) $playlistIdentifier)->where('principal_id', (string) $principalIdentifier)->first();
        if ($model === null) {
            return null;
        }
        $items = PlaylistItemModel::query()->where('playlist_id', $model->id)->orderBy('position')->get();

        return new Playlist(
            new PlaylistIdentifier($model->id),
            new PrincipalIdentifier($model->principal_id),
            new PlaylistName($model->name),
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            array_values($items->map(fn (PlaylistItemModel $item): PlaylistItem => new PlaylistItem(
                new PlaylistIdentifier($item->playlist_id),
                $item->video_link_id === null ? null : new VideoLinkIdentifier($item->video_link_id),
                VideoProvider::from($item->provider),
                new ExternalVideoId($item->external_video_id),
                $this->videoUrlBuilder->build($item->url),
                new VideoTitle($item->title),
                $item->thumbnail_url === null ? null : new ThumbnailUrl($item->thumbnail_url),
                $item->duration_seconds === null ? null : new DurationSeconds($item->duration_seconds),
                new PlaylistItemPosition($item->position),
                $item->thumbnail_path === null ? null : new ImagePath($item->thumbnail_path),
            ))->all()),
        );
    }

    public function save(Playlist $playlist): void
    {
        PlaylistModel::query()->updateOrCreate(['id' => (string) $playlist->playlistIdentifier()], [
            'principal_id' => (string) $playlist->principalIdentifier(),
            'name' => (string) $playlist->name(),
            'created_at' => $playlist->createdAt(),
            'updated_at' => $playlist->updatedAt(),
        ]);
        PlaylistItemModel::query()->where('playlist_id', (string) $playlist->playlistIdentifier())->delete();
        foreach ($playlist->items() as $item) {
            if ($item->thumbnailPath() !== null) {
                $this->videoThumbnailService->track($item->thumbnailPath());
            }
            PlaylistItemModel::query()->create([
                'playlist_id' => (string) $item->playlistIdentifier(),
                'video_link_id' => $item->videoLinkIdentifier() === null ? null : (string) $item->videoLinkIdentifier(),
                'provider' => $item->provider()->value,
                'external_video_id' => (string) $item->externalVideoId(),
                'url' => (string) $item->url(),
                'title' => (string) $item->title(),
                'thumbnail_url' => $item->thumbnailUrl() === null ? null : (string) $item->thumbnailUrl(),
                'duration_seconds' => $item->durationSeconds()?->value(),
                'position' => $item->position()->value(),
                'thumbnail_path' => $item->thumbnailPath() === null ? null : (string) $item->thumbnailPath(),
            ]);
        }
        $this->videoThumbnailService->releaseUnused();
    }

    public function deleteByIdAndPrincipal(PlaylistIdentifier $playlistIdentifier, PrincipalIdentifier $principalIdentifier): void
    {
        PlaylistModel::query()->where('id', (string) $playlistIdentifier)->where('principal_id', (string) $principalIdentifier)->delete();
        $this->videoThumbnailService->releaseUnused();
    }
}
