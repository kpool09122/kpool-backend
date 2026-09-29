<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Statement;

readonly class CreatePolicyInput implements CreatePolicyInputPort
{
    /**
     * @param Statement[] $statements
     */
    public function __construct(
        private string $name,
        private array $statements,
        private ?AccountIdentifier $accountIdentifier,
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

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
