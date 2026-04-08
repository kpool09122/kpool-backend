<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Factory;

use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Factory\MonetizationAccountFactoryInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class MonetizationAccountFactory implements MonetizationAccountFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(AccountIdentifier $accountIdentifier): MonetizationAccount
    {
        return new MonetizationAccount(
            new MonetizationAccountIdentifier($this->uuidGenerator->generate()),
            $accountIdentifier,
            [],
            null,
            null,
        );
    }
}
