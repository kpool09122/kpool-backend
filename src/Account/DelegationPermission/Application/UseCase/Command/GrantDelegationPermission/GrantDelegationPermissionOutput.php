<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

use DateTimeInterface;
use Source\Account\DelegationPermission\Domain\Entity\DelegationPermission;

class GrantDelegationPermissionOutput implements GrantDelegationPermissionOutputPort
{
    private ?DelegationPermission $delegationPermission = null;

    public function setDelegationPermission(DelegationPermission $delegationPermission): void
    {
        $this->delegationPermission = $delegationPermission;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->delegationPermission === null) {
            return [];
        }

        $dp = $this->delegationPermission;

        return [
            'delegationPermissionIdentifier' => (string) $dp->delegationPermissionIdentifier(),
            'identityGroupIdentifier' => (string) $dp->identityGroupIdentifier(),
            'targetAccountIdentifier' => (string) $dp->targetAccountIdentifier(),
            'affiliationIdentifier' => (string) $dp->affiliationIdentifier(),
            'createdAt' => $dp->createdAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
