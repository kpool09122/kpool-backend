<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

use Source\Wiki\Principal\Domain\ValueObject\Statement;

readonly class CreatePolicyInput implements CreatePolicyInputPort
{
    /**
     * @param Statement[] $statements
     */
    public function __construct(
        private string $name,
        private array $statements,
        private bool $isSystemPolicy,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Statement[]
     */
    public function statements(): array
    {
        return $this->statements;
    }

    public function isSystemPolicy(): bool
    {
        return $this->isSystemPolicy;
    }
}
