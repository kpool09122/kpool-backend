<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ListContactsByIdentityInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function targetIdentityIdentifier(): IdentityIdentifier;
}
