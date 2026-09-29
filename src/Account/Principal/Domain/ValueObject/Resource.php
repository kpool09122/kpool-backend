<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

final readonly class Resource
{
    public function __construct(
        private ResourceType $type,
        private AccountIdentifier $accountIdentifier,
        private ?AccountType $accountType = null,
        private ?AccountCategory $accountCategory = null,
        private ?AccountCategory $affiliationRequestingAccountCategory = null,
        private ?DelegationIdentifier $delegationIdentifier = null,
        private ?AccountIdentifier $targetAccountIdentifier = null,
    ) {
    }

    public static function delegationAccount(
        AccountIdentifier $sourceAccountIdentifier,
        DelegationIdentifier $delegationIdentifier,
        AccountIdentifier $targetAccountIdentifier,
    ): self {
        return new self(
            ResourceType::ACCOUNT,
            $sourceAccountIdentifier,
            delegationIdentifier: $delegationIdentifier,
            targetAccountIdentifier: $targetAccountIdentifier,
        );
    }

    public static function account(
        AccountIdentifier $accountIdentifier,
        ?AccountType $accountType = null,
        ?AccountCategory $accountCategory = null,
        ?AccountCategory $affiliationRequestingAccountCategory = null,
    ): self {
        return new self(
            ResourceType::ACCOUNT,
            $accountIdentifier,
            $accountType,
            $accountCategory,
            $affiliationRequestingAccountCategory,
        );
    }

    public function type(): ResourceType
    {
        return $this->type;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function accountType(): ?AccountType
    {
        return $this->accountType;
    }

    public function accountCategory(): ?AccountCategory
    {
        return $this->accountCategory;
    }

    public function affiliationRequestingAccountCategory(): ?AccountCategory
    {
        return $this->affiliationRequestingAccountCategory;
    }

    public function delegationIdentifier(): ?DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function targetAccountIdentifier(): ?AccountIdentifier
    {
        return $this->targetAccountIdentifier;
    }
}
