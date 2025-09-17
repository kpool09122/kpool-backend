<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Query\GetSong;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;

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
