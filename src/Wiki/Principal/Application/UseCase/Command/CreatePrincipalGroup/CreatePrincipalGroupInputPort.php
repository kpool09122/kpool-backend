<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface CreatePrincipalGroupInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function name(): string;
}
