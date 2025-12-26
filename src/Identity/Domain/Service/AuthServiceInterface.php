<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Service;

use Source\Identity\Domain\Entity\Identity;

interface AuthServiceInterface
{
    public function login(Identity $identity): Identity;

    public function logout(): void;

    public function isLoggedIn(): bool;
}
