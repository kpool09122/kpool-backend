<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\Service;

use Source\Analyze\App\Domain\ValueObject\AppCountryMetrics;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;

readonly class AppDailySummaryData
{
    /**
     * @param list<AppCountryMetrics> $countries
     * @param list<RegionalMetricObservation> $regions
     */
    public function __construct(
        private ApplicationUserCount $applicationUsers,
        private RegisteredUserSnapshot $registeredUsers,
        private array $countries,
        private array $regions,
    ) {
    }

    /** @return list<AppCountryMetrics> */
    public function countries(): array
    {
        return $this->countries;
    }

    /** @return list<RegionalMetricObservation> */
    public function regions(): array
    {
        return $this->regions;
    }

    public function applicationUsers(): ApplicationUserCount
    {
        return $this->applicationUsers;
    }

    public function registeredUsers(): RegisteredUserSnapshot
    {
        return $this->registeredUsers;
    }
}
