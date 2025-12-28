<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Domain\Factory;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Domain\Entity\User;

interface UserFactoryInterface
{
    public function create(IdentityIdentifier $identityIdentifier): User;
}
