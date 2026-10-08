<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts;

use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class ListMyContactsInput implements ListMyContactsInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
