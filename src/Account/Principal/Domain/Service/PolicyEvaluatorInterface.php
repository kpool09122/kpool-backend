<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Service;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

interface PolicyEvaluatorInterface
{
    public function evaluate(
        Principal $principal,
        Action $action,
        Resource $resource,
    ): bool;
}
