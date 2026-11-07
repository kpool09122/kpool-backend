<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Query;

use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;

readonly class WikiDailySummaryReportReadModel
{
    public function __construct(
        private MetricReportReadModel $views,
        private MetricReportReadModel $viewers,
        private MetricReportReadModel $engagementTime,
        private MetricReportReadModel $submissions,
        private MetricReportReadModel $publications,
    ) {
    }

    public function views(): MetricReportReadModel
    {
        return $this->views;
    }

    public function viewers(): MetricReportReadModel
    {
        return $this->viewers;
    }

    public function engagementTime(): MetricReportReadModel
    {
        return $this->engagementTime;
    }

    public function submissions(): MetricReportReadModel
    {
        return $this->submissions;
    }

    public function publications(): MetricReportReadModel
    {
        return $this->publications;
    }
}
