<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Infrastructure\Factory;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Factory\TransferFactoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Money;

readonly class TransferFactory implements TransferFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        SettlementBatchIdentifier $settlementBatchIdentifier,
        MonetizationAccountIdentifier $monetizationAccountIdentifier,
        Money $amount
    ): Transfer {
        return new Transfer(
            new TransferIdentifier($this->generator->generate()),
            $settlementBatchIdentifier,
            $monetizationAccountIdentifier,
            $amount
        );
    }
}
