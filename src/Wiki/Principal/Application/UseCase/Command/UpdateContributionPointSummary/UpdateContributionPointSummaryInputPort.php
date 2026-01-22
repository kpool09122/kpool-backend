<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdateContributionPointSummary;

use Source\Wiki\Principal\Domain\ValueObject\YearMonth;

interface UpdateContributionPointSummaryInputPort
{
    public function yearMonth(): YearMonth;
}
