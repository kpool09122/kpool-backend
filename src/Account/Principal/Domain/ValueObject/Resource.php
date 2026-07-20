<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

final readonly class Resource
{
    public function __construct(
        private ResourceType $type,
        private AccountIdentifier $accountIdentifier,
    ) {
    }

    public static function account(AccountIdentifier $accountIdentifier): self
    {
        return new self(ResourceType::ACCOUNT, $accountIdentifier);
    }

    public function type(): ResourceType
    {
        return $this->type;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
