<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary;

use Source\Wiki\Grading\Domain\ValueObject\YearMonth;

readonly class UpdateContributionPointSummaryInput implements UpdateContributionPointSummaryInputPort
{
    public function __construct(
        private YearMonth $yearMonth,
    ) {
    }

    public function yearMonth(): YearMonth
    {
        return $this->yearMonth;
    }
}
