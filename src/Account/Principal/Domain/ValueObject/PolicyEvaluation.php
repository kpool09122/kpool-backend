<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;

final readonly class PolicyEvaluation
{
    /** @param PrincipalGroup[]|null $principalGroups */
    public function __construct(
        public Principal $principal,
        public Action $action,
        public Resource $resource,
        public ?array $principalGroups = null,
    ) {
    }
}
