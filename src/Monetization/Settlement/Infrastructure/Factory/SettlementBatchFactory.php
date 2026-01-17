<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\Factory\SettlementBatchFactoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Currency;

readonly class SettlementBatchFactory implements SettlementBatchFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        MonetizationAccountIdentifier $monetizationAccountIdentifier,
        Currency $currency,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd
    ): SettlementBatch {
        return new SettlementBatch(
            new SettlementBatchIdentifier($this->generator->generate()),
            $monetizationAccountIdentifier,
            $currency,
            $periodStart,
            $periodEnd
        );
    }
}
