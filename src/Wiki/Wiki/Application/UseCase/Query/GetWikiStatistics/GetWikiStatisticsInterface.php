<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetWikiStatistics;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiStatisticsReadModel;

interface GetWikiStatisticsInterface
{
    /**
     * Counts public wiki pages, including each translation as a separate page.
     */
    public function process(): WikiStatisticsReadModel;
}
