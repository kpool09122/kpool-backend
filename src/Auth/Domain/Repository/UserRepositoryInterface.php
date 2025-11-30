<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Repository;

use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\Email;

interface UserRepositoryInterface
{
    public function findByEmail(Email $email): ?User;

    public function findBySocialConnection(SocialProvider $provider, string $providerUserId): ?User;

    public function save(User $user): void;
}
