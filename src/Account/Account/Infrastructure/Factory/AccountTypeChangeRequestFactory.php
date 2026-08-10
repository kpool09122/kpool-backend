<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;
use Source\Account\Account\Domain\Factory\AccountTypeChangeRequestFactoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestStatus;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountTypeChangeRequestFactory implements AccountTypeChangeRequestFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(AccountIdentifier $accountIdentifier, AccountType $currentAccountType, AccountType $requestedAccountType): AccountTypeChangeRequest
    {
        return new AccountTypeChangeRequest(
            new AccountTypeChangeRequestIdentifier($this->uuidGenerator->generate()),
            $accountIdentifier,
            $currentAccountType,
            $requestedAccountType,
            AccountTypeChangeRequestStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }
}
