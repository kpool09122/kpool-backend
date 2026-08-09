<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

final readonly class Resource
{
    public function __construct(
        private ResourceType $type,
        private AccountIdentifier $accountIdentifier,
        private ?AccountType $accountType = null,
    ) {
    }

    public static function account(AccountIdentifier $accountIdentifier, ?AccountType $accountType = null): self
    {
        return new self(ResourceType::ACCOUNT, $accountIdentifier, $accountType);
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
}
