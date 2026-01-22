<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Principal\Domain\Entity\ContributionPointHistory;
use Source\Wiki\Principal\Domain\Factory\ContributionPointHistoryFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\ContributionPointHistoryIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\ContributorType;
use Source\Wiki\Principal\Domain\ValueObject\Point;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class ContributionPointHistoryFactory implements ContributionPointHistoryFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        PrincipalIdentifier $principalIdentifier,
        YearMonth           $yearMonth,
        Point               $points,
        ResourceType        $resourceType,
        string              $resourceId,
        ContributorType     $roleType,
        bool                $isNewCreation,
        DateTimeImmutable   $createdAt,
    ): ContributionPointHistory {
        return new ContributionPointHistory(
            new ContributionPointHistoryIdentifier($this->uuidGenerator->generate()),
            $principalIdentifier,
            $yearMonth,
            $points,
            $resourceType,
            new ResourceIdentifier($resourceId),
            $roleType,
            $isNewCreation,
            $createdAt,
        );
    }
}
