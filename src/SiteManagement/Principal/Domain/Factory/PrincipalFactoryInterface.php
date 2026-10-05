<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Factory;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;

interface PrincipalFactoryInterface
{
    public function create(IdentityIdentifier $identityIdentifier): Principal;
}
