<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreateRole;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

readonly class CreateRoleInput implements CreateRoleInputPort
{
    /**
     * @param PolicyIdentifier[] $policies
     */
    public function __construct(
        private string $name,
        private array $policies,
        private ?AccountIdentifier $accountIdentifier,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return PolicyIdentifier[]
     */
    public function policies(): array
    {
        return $this->policies;
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
