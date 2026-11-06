<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary;

use LogicException;

class ImportWikiDailySummaryOutput implements ImportWikiDailySummaryOutputPort
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
