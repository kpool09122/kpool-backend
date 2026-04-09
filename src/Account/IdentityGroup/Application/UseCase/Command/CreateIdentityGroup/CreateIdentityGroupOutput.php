<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup;

use DateTimeInterface;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;

class CreateIdentityGroupOutput implements CreateIdentityGroupOutputPort
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
            'createdAt' => $ig->createdAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
