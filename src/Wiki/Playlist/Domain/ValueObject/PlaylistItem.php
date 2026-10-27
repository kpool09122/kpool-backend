<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;

final readonly class PlaylistItem
{
    public function __construct(
        private PlaylistIdentifier $playlistIdentifier,
        private ?VideoLinkIdentifier $videoLinkIdentifier,
        private VideoProvider $provider,
        private ExternalVideoId $externalVideoId,
        private YouTubeVideoUrl|TikTokVideoUrl $url,
        private VideoTitle $title,
        private ?ThumbnailUrl $thumbnailUrl,
        private ?DurationSeconds $durationSeconds,
        private PlaylistItemPosition $position,
        private ?ImagePath $thumbnailPath = null,
    ) {
        if ($url->provider() !== $provider->value || $url->externalVideoId() !== (string) $externalVideoId) {
            throw new InvalidArgumentException('Video identifier and snapshot URL must match.');
        }
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

    public function url(): YouTubeVideoUrl|TikTokVideoUrl
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

    public function atPosition(PlaylistItemPosition $position): self
    {
        return new self($this->playlistIdentifier, $this->videoLinkIdentifier, $this->provider, $this->externalVideoId, $this->url, $this->title, $this->thumbnailUrl, $this->durationSeconds, $position, $this->thumbnailPath);
    }

    public function thumbnailPath(): ?ImagePath
    {
        return $this->thumbnailPath;
    }

    public function position(): PlaylistItemPosition
    {
        return $this->position;
    }
}
