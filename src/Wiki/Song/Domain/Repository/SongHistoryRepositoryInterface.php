<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Repository;

use Source\Wiki\Song\Domain\Entity\SongHistory;

interface SongHistoryRepositoryInterface
{
    public function save(SongHistory $songHistory): void;
}
