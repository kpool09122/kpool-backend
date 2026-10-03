<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser;

use Source\SiteManagement\User\Domain\Entity\User;

class ProvisionUserOutput implements ProvisionUserOutputPort
{
    private ?User $user = null;

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if ($this->user === null) {
            return [];
        }
        $user = $this->user;

        return [
            'userIdentifier' => (string) $user->userIdentifier(),
            'identityIdentifier' => (string) $user->identityIdentifier(),
            'role' => $user->role()->value,
        ];
    }
}
