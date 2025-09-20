<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Repository;

use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface SongRepositoryInterface
{
    public function findById(SongIdentifier $songIdentifier): ?Song;

    public function save(Song $song): void;
}
