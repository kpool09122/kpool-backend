<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface CreatePrincipalInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function identityIdentifier(): IdentityIdentifier;
}
