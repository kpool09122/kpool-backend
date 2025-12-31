<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Factory;

use DateTimeImmutable;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class SettlementBatchFactory implements SettlementBatchFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        SettlementAccount $account,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd
    ): SettlementBatch {
        return new SettlementBatch(
            new SettlementBatchIdentifier($this->generator->generate()),
            $account,
            $periodStart,
            $periodEnd
        );
    }
}
