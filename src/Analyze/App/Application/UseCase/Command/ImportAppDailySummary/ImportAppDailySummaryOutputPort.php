<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary;

interface ImportAppDailySummaryOutputPort
{
    public function setSummaryCount(int $summaryCount): void;
}
