<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface CreatePrincipalInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function accountIdentifier(): AccountIdentifier;
}
