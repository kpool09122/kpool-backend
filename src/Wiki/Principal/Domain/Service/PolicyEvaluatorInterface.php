<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Service;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;

interface PolicyEvaluatorInterface
{
    public function evaluate(
        Principal $principal,
        Action $action,
        ResourceIdentifier $resource,
    ): bool;
}
