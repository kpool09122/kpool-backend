<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Service;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\PolicyEvaluation;
use Source\Account\Principal\Domain\ValueObject\Resource;

interface PolicyEvaluatorInterface
{
    /**
     * @param array<string, PolicyEvaluation> $evaluations
     * @return array<string, bool>
     */
    public function evaluateMany(array $evaluations): array;

    /** @param PrincipalGroup[]|null $principalGroups */
    public function evaluate(
        Principal $principal,
        Action $action,
        Resource $resource,
        ?array $principalGroups = null,
    ): bool;
}
