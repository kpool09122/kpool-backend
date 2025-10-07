<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\PublishSong;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class PublishSongInput implements PublishSongInputPort
{
    public function __construct(
        private SongIdentifier  $songIdentifier,
        private ?SongIdentifier $publishedSongIdentifier,
        private Principal       $principal,
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

    public function principal(): Principal
    {
        return $this->principal;
    }
}
