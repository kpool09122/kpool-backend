<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface SongRepositoryInterface
{
    public function findById(SongIdentifier $songIdentifier): ?Song;

    public function existsBySlug(Slug $slug): bool;

    /**
     * @return Song[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array;

    public function save(Song $song): void;
}
