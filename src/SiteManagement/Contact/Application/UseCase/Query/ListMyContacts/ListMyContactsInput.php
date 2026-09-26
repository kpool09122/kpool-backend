<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ListMyContactsInput implements ListMyContactsInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
