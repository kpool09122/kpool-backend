<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Factory;

use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\ValueObject\Statement;

interface PolicyFactoryInterface
{
    /**
     * @param Statement[] $statements
     */
    public function create(
        string $name,
        array $statements,
        bool $isSystemPolicy,
    ): Policy;
}
