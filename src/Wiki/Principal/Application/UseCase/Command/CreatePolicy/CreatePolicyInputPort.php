<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

use Source\Wiki\Principal\Domain\ValueObject\Statement;

interface CreatePolicyInputPort
{
    public function name(): string;

    /**
     * @return Statement[]
     */
    public function statements(): array;

    public function isSystemPolicy(): bool;
}
