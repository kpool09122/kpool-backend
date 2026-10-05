<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Service;

use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;

interface PolicyEvaluatorInterface
{
    public function evaluate(Principal $principal, Action $action, Resource $resource): bool;
}
