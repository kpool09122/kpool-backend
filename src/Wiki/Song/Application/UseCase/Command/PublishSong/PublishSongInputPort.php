<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\PublishSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface PublishSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function publishedSongIdentifier(): ?SongIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
