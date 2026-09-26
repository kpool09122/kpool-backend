<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ListMyContactsInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
