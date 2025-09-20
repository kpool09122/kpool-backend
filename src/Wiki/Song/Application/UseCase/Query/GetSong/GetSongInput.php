<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Query\GetSong;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class GetSongInput implements GetSongInputPort
{
    public function __construct(
        private SongIdentifier $songIdentifier,
        private Translation $translation,
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }
}
