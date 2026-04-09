<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup;

use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class RemoveIdentityFromIdentityGroupOutput implements RemoveIdentityFromIdentityGroupOutputPort
{
    private ?IdentityGroup $identityGroup = null;

    public function setIdentityGroup(IdentityGroup $identityGroup): void
    {
        $this->identityGroup = $identityGroup;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->identityGroup === null) {
            return [];
        }

        $ig = $this->identityGroup;

        return [
            'identityGroupIdentifier' => (string) $ig->identityGroupIdentifier(),
            'accountIdentifier' => (string) $ig->accountIdentifier(),
            'name' => $ig->name(),
            'role' => $ig->role()->value,
            'isDefault' => $ig->isDefault(),
            'members' => array_map(
                static fn (IdentityIdentifier $id) => (string) $id,
                array_values($ig->members()),
            ),
        ];
    }
}
