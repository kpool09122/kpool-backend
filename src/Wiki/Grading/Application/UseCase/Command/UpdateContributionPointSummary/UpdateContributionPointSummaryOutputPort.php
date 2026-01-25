<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary;

interface UpdateContributionPointSummaryOutputPort
{
    public function setUpdatedCount(int $count): void;

    public function updatedCount(): int;
}
