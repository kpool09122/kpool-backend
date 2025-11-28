<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Query\GetSong;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class GetSongInput implements GetSongInputPort
{
    public function __construct(
        private SongIdentifier $songIdentifier,
        private Language       $language,
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }
}
