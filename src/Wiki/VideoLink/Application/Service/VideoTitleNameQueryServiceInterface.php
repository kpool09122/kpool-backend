<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Service;

use Source\Wiki\Wiki\Domain\Entity\Wiki;

interface VideoTitleNameQueryServiceInterface
{
    /** @return array<string, list<string>> Names grouped by language code. */
    public function findNames(Wiki $wiki): array;
}
