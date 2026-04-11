<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

use Source\Wiki\Principal\Domain\Entity\Policy;

interface CreatePolicyOutputPort
{
    public function setPolicy(Policy $policy): void;

    /**
     * @return array{policyIdentifier: ?string, name: ?string, isSystemPolicy: ?bool, createdAt: ?string}
     */
    public function toArray(): array;
}
