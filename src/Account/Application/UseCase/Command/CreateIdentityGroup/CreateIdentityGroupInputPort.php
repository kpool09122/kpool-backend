<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\CreateIdentityGroup;

use Source\Account\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface CreateIdentityGroupInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function name(): string;

    public function role(): AccountRole;
}
