<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\Factory;

use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PrincipalFactoryInterface
{
    public function create(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): Principal;
}
