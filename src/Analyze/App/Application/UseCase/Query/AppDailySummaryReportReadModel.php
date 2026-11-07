<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query;

use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;

readonly class AppDailySummaryReportReadModel
{
    public function __construct(
        private MetricReportReadModel $applicationUsers,
        private MetricReportReadModel $registeredUsers,
    ) {
    }

    public function applicationUsers(): MetricReportReadModel
    {
        return $this->applicationUsers;
    }

    public function registeredUsers(): MetricReportReadModel
    {
        return $this->registeredUsers;
    }
}
