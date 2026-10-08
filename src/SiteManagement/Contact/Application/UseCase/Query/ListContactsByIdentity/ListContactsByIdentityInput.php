<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class ListContactsByIdentityInput implements ListContactsByIdentityInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private IdentityIdentifier $targetIdentityIdentifier,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function targetIdentityIdentifier(): IdentityIdentifier
    {
        return $this->targetIdentityIdentifier;
    }
}
