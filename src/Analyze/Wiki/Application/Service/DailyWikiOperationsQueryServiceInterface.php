<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\Service;

use DateTimeImmutable;
use Source\Analyze\Wiki\Application\Exception\WikiOperationHistoryUnavailableException;

interface DailyWikiOperationsQueryServiceInterface
{
    /**
     * @return list<DailyWikiOperationCount>
     * @throws WikiOperationHistoryUnavailableException
     */
    public function find(DateTimeImmutable $start, DateTimeImmutable $endExclusive): array;
}
