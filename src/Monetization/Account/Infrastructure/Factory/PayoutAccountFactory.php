<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Factory;

use Source\Monetization\Account\Domain\Entity\PayoutAccount;
use Source\Monetization\Account\Domain\Factory\PayoutAccountFactoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class PayoutAccountFactory implements PayoutAccountFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        MonetizationAccountIdentifier $monetizationAccountIdentifier,
        ExternalAccountId $externalAccountId,
    ): PayoutAccount {
        return new PayoutAccount(
            new PayoutAccountIdentifier($this->uuidGenerator->generate()),
            $monetizationAccountIdentifier,
            $externalAccountId,
        );
    }
}
