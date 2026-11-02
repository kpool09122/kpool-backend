<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary;

use LogicException;

class ImportAppDailySummaryOutput implements ImportAppDailySummaryOutputPort
{
    private ?int $summaryCount = null;

    public function setSummaryCount(int $summaryCount): void
    {
        $this->summaryCount = $summaryCount;
    }

    public function summaryCount(): int
    {
        return $this->summaryCount ?? throw new LogicException('Daily summary import has not completed.');
    }
}
