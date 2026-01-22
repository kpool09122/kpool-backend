<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdateContributionPointSummary;

interface UpdateContributionPointSummaryInterface
{
    public function process(
        UpdateContributionPointSummaryInputPort $input,
        UpdateContributionPointSummaryOutputPort $output,
    ): void;
}
