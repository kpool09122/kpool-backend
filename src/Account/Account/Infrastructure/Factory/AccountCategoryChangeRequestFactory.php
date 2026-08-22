<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Factory\AccountCategoryChangeRequestFactoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountCategoryChangeRequestFactory implements AccountCategoryChangeRequestFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(AccountIdentifier $accountIdentifier, AccountCategory $currentAccountCategory, AccountCategory $requestedAccountCategory): AccountCategoryChangeRequest
    {
        return new AccountCategoryChangeRequest(
            new AccountCategoryChangeRequestIdentifier($this->uuidGenerator->generate()),
            $accountIdentifier,
            $currentAccountCategory,
            $requestedAccountCategory,
            AccountCategoryChangeRequestStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }
}
