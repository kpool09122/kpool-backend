<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Repository;

use Source\Wiki\Wiki\Domain\Entity\WikiHistory;

interface WikiHistoryRepositoryInterface
{
    public function save(WikiHistory $wikiHistory): void;
}
