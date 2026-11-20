<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

final readonly class Resource
{
    public function __construct(private ResourceType $type, private AccountIdentifier $accountIdentifier)
    {
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
