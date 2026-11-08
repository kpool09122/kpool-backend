<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query;

use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;

/** @phpstan-import-type SerializedMetricReport from MetricReportReadModel */
readonly class AppDailySummaryReportReadModel
{
    public function __construct(
        private MetricReportReadModel $applicationUsers,
        private MetricReportReadModel $registeredUsers,
    ) {
    }

    /** @return array{applicationUsers: SerializedMetricReport, registeredUsers: SerializedMetricReport} */
    public function toArray(): array
    {
        return [
            'applicationUsers' => $this->applicationUsers->toArray(),
            'registeredUsers' => $this->registeredUsers->toArray(),
        ];
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
