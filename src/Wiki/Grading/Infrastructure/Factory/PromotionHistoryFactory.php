<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Grading\Domain\Entity\PromotionHistory;
use Source\Wiki\Grading\Domain\Facotory\PromotionHistoryFactoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\PromotionHistoryIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class PromotionHistoryFactory implements PromotionHistoryFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        PrincipalIdentifier $principalIdentifier,
        string $fromRole,
        string $toRole,
        ?string $reason,
    ): PromotionHistory {
        return new PromotionHistory(
            new PromotionHistoryIdentifier($this->uuidGenerator->generate()),
            $principalIdentifier,
            $fromRole,
            $toRole,
            $reason,
            new DateTimeImmutable(),
        );
    }
}
