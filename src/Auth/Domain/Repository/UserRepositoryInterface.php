<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Repository;

use Source\Auth\Domain\Entity\User;
use Source\Shared\Domain\ValueObject\Email;

interface UserRepositoryInterface
{
    public function findByEmail(Email $email): ?User;

    public function save(User $user): void;
}
