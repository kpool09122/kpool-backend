<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Account\Domain\Entity\ArchivedAccount;
use Source\Account\Account\Domain\Factory\ArchivedAccountFactoryInterface;
use Source\Account\Account\Domain\ValueObject\ArchivedAccountIdentifier;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class ArchivedAccountFactory implements ArchivedAccountFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(
        AccountIdentifier $accountIdentifier,
        AccountCategory $accountCategory,
        AccountType $accountType,
    ): ArchivedAccount {
        return new ArchivedAccount(
            new ArchivedAccountIdentifier($this->uuidGenerator->generate()),
            $accountIdentifier,
            $accountCategory,
            $accountType,
            new DateTimeImmutable(),
        );
    }
}
