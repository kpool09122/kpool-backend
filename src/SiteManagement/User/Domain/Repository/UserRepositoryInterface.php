<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Domain\Repository;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Domain\Entity\User;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

interface UserRepositoryInterface
{
    public function findById(UserIdentifier $userIdentifier): ?User;

    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): ?User;

    public function save(User $user): void;
}
