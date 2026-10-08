<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal;

use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class ListContactsByPrincipalInput implements ListContactsByPrincipalInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private PrincipalIdentifier $targetPrincipalIdentifier,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function targetPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->targetPrincipalIdentifier;
    }
}
