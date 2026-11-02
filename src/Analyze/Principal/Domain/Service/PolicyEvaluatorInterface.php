<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\Service;

use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Analyze\Principal\Domain\Entity\PrincipalGroup;
use Source\Analyze\Principal\Domain\ValueObject\Action;
use Source\Analyze\Principal\Domain\ValueObject\Resource;

interface PolicyEvaluatorInterface
{
    /** @param array<PrincipalGroup>|null $principalGroups */
    public function evaluate(Principal $principal, Action $action, Resource $resource, ?array $principalGroups = null): bool;
}
