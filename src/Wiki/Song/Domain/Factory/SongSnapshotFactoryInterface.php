<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Factory;

use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Entity\SongSnapshot;

interface SongSnapshotFactoryInterface
{
    public function create(Song $song): SongSnapshot;
}
