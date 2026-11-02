<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\Service;

use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;

readonly class AppDailySummaryData
{
    public function __construct(
        private ApplicationUserCount $applicationUsers,
        private RegisteredUserSnapshot $registeredUsers,
    ) {
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
