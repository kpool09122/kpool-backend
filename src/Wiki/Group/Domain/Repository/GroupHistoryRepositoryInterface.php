<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Repository;

use Source\Wiki\Group\Domain\Entity\GroupHistory;

interface GroupHistoryRepositoryInterface
{
    public function save(GroupHistory $groupHistory): void;
}
