<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\PrincipalGroupDescription;

interface CreatePrincipalGroupInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function name(): string;

    public function description(): ?PrincipalGroupDescription;
}
