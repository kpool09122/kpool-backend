<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreateRole;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

interface CreateRoleInputPort
{
    public function name(): string;

    /**
     * @return PolicyIdentifier[]
     */
    public function policies(): array;

    public function accountIdentifier(): ?AccountIdentifier;
}
