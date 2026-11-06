<?php

declare(strict_types=1);

namespace Source\Analyze\App\Domain\Factory;

use Source\Analyze\App\Domain\Entity\AppDailySummary;
use Source\Analyze\App\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;

interface AppDailySummaryFactoryInterface
{
    /** @throws InvalidDailySummaryException */
    public function create(ApplicationUserCount $applicationUsers, RegisteredUserSnapshot $registeredUsers): AppDailySummary;
}
