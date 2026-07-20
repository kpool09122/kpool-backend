<?php

declare(strict_types=1);

namespace Source\Account\Policy\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

final readonly class AccountResource
{
    public function __construct(
        private AccountResourceType $type,
        private AccountIdentifier $accountIdentifier,
    ) {
    }

    public static function account(AccountIdentifier $accountIdentifier): self
    {
        return new self(AccountResourceType::ACCOUNT, $accountIdentifier);
    }

    public function type(): AccountResourceType
    {
        return $this->type;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
