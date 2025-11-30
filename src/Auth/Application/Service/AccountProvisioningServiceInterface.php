<?php

declare(strict_types=1);

namespace Source\Auth\Application\Service;

use Source\Shared\Domain\ValueObject\UserIdentifier;

interface AccountProvisioningServiceInterface
{
    public function provision(UserIdentifier $userIdentifier): void;
}
