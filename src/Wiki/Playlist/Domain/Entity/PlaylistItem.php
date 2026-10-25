<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\Entity;

use InvalidArgumentException;
use Source\Wiki\Playlist\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;

readonly class PlaylistItem
{
    public function __construct(
        private PlaylistItemIdentifier $playlistItemIdentifier,
        private PlaylistIdentifier $playlistIdentifier,
        private ?VideoLinkIdentifier $videoLinkIdentifier,
        private VideoProvider $provider,
        private ExternalVideoId $externalVideoId,
        private YouTubeVideoUrl $url,
        private VideoTitle $title,
        private ?ThumbnailUrl $thumbnailUrl,
        private ?DurationSeconds $durationSeconds,
        private int $position,
    ) {
        if ($position < 0) {
            throw new InvalidArgumentException('Playlist item position must be non-negative.');
        }
        if ((string) $url !== 'https://www.youtube.com/watch?v=' . $externalVideoId) {
            throw new InvalidArgumentException('Video identifier and snapshot URL must match.');
        }
    }

    public function playlistItemIdentifier(): PlaylistItemIdentifier
    {
        return $this->playlistItemIdentifier;
    }

    public function playlistIdentifier(): PlaylistIdentifier
    {
        return $this->playlistIdentifier;
    }

    public function videoLinkIdentifier(): ?VideoLinkIdentifier
    {
        return $this->videoLinkIdentifier;
    }

    public function provider(): VideoProvider
    {
        return $this->provider;
    }

    public function externalVideoId(): ExternalVideoId
    {
        return $this->externalVideoId;
    }

    public function url(): YouTubeVideoUrl
    {
        return $this->url;
    }

    public function title(): VideoTitle
    {
        return $this->title;
    }

    public function thumbnailUrl(): ?ThumbnailUrl
    {
        return $this->thumbnailUrl;
    }

    public function durationSeconds(): ?DurationSeconds
    {
        return $this->durationSeconds;
    }

    public function position(): int
    {
        return $this->position;
    }
}
