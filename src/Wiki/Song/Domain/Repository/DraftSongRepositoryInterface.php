<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface DraftSongRepositoryInterface
{
    public function findById(SongIdentifier $songIdentifier): ?DraftSong;

    public function save(DraftSong $song): void;

    public function delete(DraftSong $song): void;

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return DraftSong[]
     */
    public function findByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array;
}
