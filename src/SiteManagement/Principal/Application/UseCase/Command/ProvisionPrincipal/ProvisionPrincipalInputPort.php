<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ProvisionPrincipalInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function identityIdentifier(): IdentityIdentifier;
}
