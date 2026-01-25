<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary;

class UpdateContributionPointSummaryOutput implements UpdateContributionPointSummaryOutputPort
{
    private int $updatedCount = 0;

    public function setUpdatedCount(int $count): void
    {
        $this->updatedCount = $count;
    }

    public function updatedCount(): int
    {
        return $this->updatedCount;
    }
}
