<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Repository;

use Source\Wiki\Talent\Domain\Entity\TalentHistory;

interface TalentHistoryRepositoryInterface
{
    public function save(TalentHistory $talentHistory): void;
}
