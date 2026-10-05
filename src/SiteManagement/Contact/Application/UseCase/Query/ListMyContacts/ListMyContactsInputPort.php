<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts;

use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ListMyContactsInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;
}
