<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal;

use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ListContactsByPrincipalInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function targetPrincipalIdentifier(): PrincipalIdentifier;
}
