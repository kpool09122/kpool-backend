<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;

class AddPrincipalToPrincipalGroupOutput implements AddPrincipalToPrincipalGroupOutputPort
{
    private ?PrincipalGroup $principalGroup = null;

    public function setPrincipalGroup(PrincipalGroup $principalGroup): void
    {
        $this->principalGroup = $principalGroup;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->principalGroup === null) {
            return [];
        }

        $ig = $this->principalGroup;

        return [
            'principalGroupIdentifier' => (string) $ig->principalGroupIdentifier(),
            'accountIdentifier' => (string) $ig->accountIdentifier(),
            'name' => $ig->name(),
            'role' => $ig->role()->value,
            'isDefault' => $ig->isDefault(),
            'members' => array_map(
                static fn (Principal $principal) => (string) $principal->principalIdentifier(),
                array_values($ig->members()),
            ),
        ];
    }
}
