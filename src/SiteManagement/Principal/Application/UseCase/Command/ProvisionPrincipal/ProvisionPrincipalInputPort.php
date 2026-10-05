<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ProvisionPrincipalInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
