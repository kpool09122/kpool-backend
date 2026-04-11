<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use DateTimeInterface;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;

class AddPrincipalToPrincipalGroupOutput implements AddPrincipalToPrincipalGroupOutputPort
{
    private ?PrincipalGroup $principalGroup = null;

    public function setPrincipalGroup(PrincipalGroup $principalGroup): void
    {
        $this->principalGroup = $principalGroup;
    }

    /**
     * @return array{principalGroupIdentifier: ?string, accountIdentifier: ?string, name: ?string, isDefault: ?bool, memberCount: ?int, createdAt: ?string}
     */
    public function toArray(): array
    {
        if ($this->principalGroup === null) {
            return [
                'principalGroupIdentifier' => null,
                'accountIdentifier' => null,
                'name' => null,
                'isDefault' => null,
                'memberCount' => null,
                'createdAt' => null,
            ];
        }

        return [
            'principalGroupIdentifier' => (string) $this->principalGroup->principalGroupIdentifier(),
            'accountIdentifier' => (string) $this->principalGroup->accountIdentifier(),
            'name' => $this->principalGroup->name(),
            'isDefault' => $this->principalGroup->isDefault(),
            'memberCount' => $this->principalGroup->memberCount(),
            'createdAt' => $this->principalGroup->createdAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
