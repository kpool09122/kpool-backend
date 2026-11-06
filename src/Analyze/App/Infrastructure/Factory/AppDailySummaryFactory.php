<?php

declare(strict_types=1);

namespace Source\Analyze\App\Infrastructure\Factory;

use Source\Analyze\App\Domain\Entity\AppDailySummary;
use Source\Analyze\App\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\App\Domain\Factory\AppDailySummaryFactoryInterface;
use Source\Analyze\App\Domain\ValueObject\AppDailySummaryIdentifier;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class AppDailySummaryFactory implements AppDailySummaryFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    /** @throws InvalidDailySummaryException */
    public function create(ApplicationUserCount $applicationUsers, RegisteredUserSnapshot $registeredUsers): AppDailySummary
    {
        return new AppDailySummary(
            new AppDailySummaryIdentifier($this->uuidGenerator->generate()),
            $applicationUsers->provenance()->day(),
            $applicationUsers,
            $registeredUsers,
        );
    }
}
