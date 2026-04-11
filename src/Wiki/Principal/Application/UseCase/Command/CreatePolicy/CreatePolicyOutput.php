<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

use DateTimeInterface;
use Source\Wiki\Principal\Domain\Entity\Policy;

class CreatePolicyOutput implements CreatePolicyOutputPort
{
    private ?Policy $policy = null;

    public function setPolicy(Policy $policy): void
    {
        $this->policy = $policy;
    }

    /**
     * @return array{policyIdentifier: ?string, name: ?string, isSystemPolicy: ?bool, createdAt: ?string}
     */
    public function toArray(): array
    {
        if ($this->policy === null) {
            return [
                'policyIdentifier' => null,
                'name' => null,
                'isSystemPolicy' => null,
                'createdAt' => null,
            ];
        }

        return [
            'policyIdentifier' => (string) $this->policy->policyIdentifier(),
            'name' => $this->policy->name(),
            'isSystemPolicy' => $this->policy->isSystemPolicy(),
            'createdAt' => $this->policy->createdAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
