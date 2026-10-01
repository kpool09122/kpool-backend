<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser;

use Source\SiteManagement\User\Domain\Entity\User;

interface ProvisionUserOutputPort
{
    public function setUser(User $user): void;

    public function user(): ?User;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
