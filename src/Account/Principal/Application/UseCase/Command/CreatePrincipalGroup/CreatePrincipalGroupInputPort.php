<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface CreatePrincipalGroupInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function name(): string;

    public function role(): AccountRole;
}
