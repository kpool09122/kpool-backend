<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Service;

use Source\Auth\Domain\Entity\User;

interface AuthServiceInterface
{
    public function login(User $user): User;

    public function logout(): void;

    public function isLoggedIn(): bool;
}
