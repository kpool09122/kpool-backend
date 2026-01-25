<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Grading\Domain\Entity\DemotionWarning;
use Source\Wiki\Grading\Domain\Facotory\DemotionWarningFactoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\DemotionWarningIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\WarningCount;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class DemotionWarningFactory implements DemotionWarningFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $lastWarningMonth,
    ): DemotionWarning {
        $now = new DateTimeImmutable();

        return new DemotionWarning(
            new DemotionWarningIdentifier($this->uuidGenerator->generate()),
            $principalIdentifier,
            new WarningCount(1),
            $lastWarningMonth,
            $now,
            $now,
        );
    }
}
