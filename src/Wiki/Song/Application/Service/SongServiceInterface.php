<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\Service;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface SongServiceInterface
{
    public function existsApprovedButNotTranslatedSong(
        SongIdentifier $songIdentifier,
        SongIdentifier $publishedSongIdentifier,
    ): bool;

    public function translateSong(
        Song  $song,
        Translation $translation,
    ): DraftSong;
}
