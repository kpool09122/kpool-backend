<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

final readonly class Resource
{
    public function __construct(private ResourceType $type, private ?IdentityIdentifier $ownerIdentityIdentifier = null)
    {
    }

    public function type(): ResourceType
    {
        return $this->type;
    }

    public function ownerIdentityIdentifier(): ?IdentityIdentifier
    {
        return $this->ownerIdentityIdentifier;
    }
}
