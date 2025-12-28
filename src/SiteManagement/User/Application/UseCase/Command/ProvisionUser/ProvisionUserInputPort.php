<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ProvisionUserInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
