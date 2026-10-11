<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\ValueObject;

final readonly class Resource
{
    public function __construct(private ResourceType $type, private ?PrincipalIdentifier $ownerPrincipalIdentifier = null)
    {
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
