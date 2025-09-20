<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

readonly class Actor
{
    /**
     * @param Role $role
     * @param string|null $agencyId
     * @param string[] $groupIds
     * @param string|null $memberId
     */
    public function __construct(
        private Role $role,
        private ?string $agencyId,
        private array $groupIds,
        private ?string $memberId,
    ) {
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function agencyId(): ?string
    {
        return $this->agencyId;
    }

    /**
     * @return string[]
     */
    public function groupIds(): array
    {
        return $this->groupIds;
    }

    public function memberId(): ?string
    {
        return $this->memberId;
    }
}
