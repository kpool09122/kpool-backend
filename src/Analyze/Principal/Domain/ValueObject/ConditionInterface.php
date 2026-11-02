<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\ValueObject;

use Source\Analyze\Principal\Domain\Entity\Principal;

interface ConditionInterface
{
    public function matches(Principal $principal, Resource $resource): bool;
}
