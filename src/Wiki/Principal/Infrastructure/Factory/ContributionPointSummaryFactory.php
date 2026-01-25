<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Principal\Domain\Entity\ContributionPointSummary;
use Source\Wiki\Principal\Domain\Factory\ContributionPointSummaryFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\ContributionPointSummaryIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Point;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ContributionPointSummaryFactory implements ContributionPointSummaryFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $yearMonth,
        Point $points,
    ): ContributionPointSummary {
        $now = new DateTimeImmutable();

        return new ContributionPointSummary(
            new ContributionPointSummaryIdentifier($this->uuidGenerator->generate()),
            $principalIdentifier,
            $yearMonth,
            $points,
            $now,
            $now,
        );
    }
}
