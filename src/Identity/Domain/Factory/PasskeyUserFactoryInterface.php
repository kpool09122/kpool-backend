<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Factory;

use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PasskeyUserFactoryInterface
{
    public function create(?IdentityIdentifier $identityIdentifier = null): PasskeyUser;
}
