<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\Domain\Repository;

use Businesses\Wiki\Song\Domain\Entity\Song;
use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface SongRepositoryInterface
{
    public function findById(SongIdentifier $songIdentifier): ?Song;

    public function save(Song $song): void;
}
