<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreateRole;

use DateTimeInterface;
use Source\Wiki\Principal\Domain\Entity\Role;

class CreateRoleOutput implements CreateRoleOutputPort
{
    private ?Role $role = null;

    public function setRole(Role $role): void
    {
        $this->role = $role;
    }

    /**
     * @return array{roleIdentifier: ?string, name: ?string, isSystemRole: ?bool, createdAt: ?string}
     */
    public function toArray(): array
    {
        if ($this->role === null) {
            return [
                'roleIdentifier' => null,
                'name' => null,
                'isSystemRole' => null,
                'createdAt' => null,
            ];
        }

        return [
            'roleIdentifier' => (string) $this->role->roleIdentifier(),
            'name' => $this->role->name(),
            'isSystemRole' => $this->role->isSystemRole(),
            'createdAt' => $this->role->createdAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
