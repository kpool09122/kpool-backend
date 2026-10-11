<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

final readonly class Resource
{
    public function __construct(private ResourceType $type, private ?PrincipalIdentifier $ownerPrincipalIdentifier = null, private ?AccountIdentifier $accountIdentifier = null)
    {
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function type(): ResourceType
    {
        return $this->type;
    }

    public function ownerPrincipalIdentifier(): ?PrincipalIdentifier
    {
        return $this->ownerPrincipalIdentifier;
    }
}
