<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ListContactsInput implements ListContactsInputPort
{
    public function __construct(
        private IdentityIdentifier $requesterIdentityIdentifier,
        private ?IdentityIdentifier $targetIdentityIdentifier,
    ) {
    }

    public function requesterIdentityIdentifier(): IdentityIdentifier
    {
        return $this->requesterIdentityIdentifier;
    }

    public function targetIdentityIdentifier(): ?IdentityIdentifier
    {
        return $this->targetIdentityIdentifier;
    }
}
