<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Service;

use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;

interface PolicyEvaluatorInterface
{
    /** @param array<PrincipalGroup>|null $principalGroups */
    public function evaluate(Principal $principal, Action $action, Resource $resource, ?array $principalGroups = null): bool;
}
