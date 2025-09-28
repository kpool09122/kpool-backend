<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\ApproveUpdatedSong;

use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class ApproveUpdatedSongInput implements ApproveUpdatedSongInputPort
{
    public function __construct(
        private SongIdentifier  $songIdentifier,
        private ?SongIdentifier $publishedSongIdentifier,
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function publishedSongIdentifier(): ?SongIdentifier
    {
        return $this->publishedSongIdentifier;
    }
}
