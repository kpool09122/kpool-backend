<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\Service;

use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Resource;

interface PolicyEvaluatorInterface
{
    /** @param array<PrincipalGroup>|null $principalGroups */
    public function evaluate(Principal $principal, Action $action, Resource $resource, ?array $principalGroups = null): bool;
}
