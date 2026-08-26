<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ListContactsByIdentityInput implements ListContactsByIdentityInputPort
{
    public function __construct(
        private IdentityIdentifier $requesterIdentityIdentifier,
        private IdentityIdentifier $targetIdentityIdentifier,
    ) {
    }

    public function requesterIdentityIdentifier(): IdentityIdentifier
    {
        return $this->requesterIdentityIdentifier;
    }

    public function targetIdentityIdentifier(): IdentityIdentifier
    {
        return $this->targetIdentityIdentifier;
    }
}
