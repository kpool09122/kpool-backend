<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\Service;

use Source\Analyze\App\Domain\ValueObject\AppCountryMetrics;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;

readonly class AppDailySummaryData
{
    /** @param list<AppCountryMetrics> $countries */
    public function __construct(
        private ApplicationUserCount $applicationUsers,
        private RegisteredUserSnapshot $registeredUsers,
        private array $countries,
    ) {
    }

    /** @return list<AppCountryMetrics> */
    public function countries(): array
    {
        return $this->countries;
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
