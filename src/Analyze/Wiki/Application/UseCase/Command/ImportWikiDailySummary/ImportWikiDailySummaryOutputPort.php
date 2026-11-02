<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary;

interface ImportWikiDailySummaryOutputPort
{
    public function setSummaryCount(int $summaryCount): void;
}
