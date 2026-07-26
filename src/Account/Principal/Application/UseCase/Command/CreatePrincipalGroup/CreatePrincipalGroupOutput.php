<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use DateTimeInterface;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;

class CreatePrincipalGroupOutput implements CreatePrincipalGroupOutputPort
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
            'roleIdentifiers' => array_map(static fn ($roleIdentifier): string => (string) $roleIdentifier, $ig->roles()),
            'isDefault' => $ig->isDefault(),
            'createdAt' => $ig->createdAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
