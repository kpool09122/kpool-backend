<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Repository;

use Source\Auth\Domain\Entity\AuthCodeSession;

interface AuthCodeSessionRepositoryInterface
{
    public function save(AuthCodeSession $authCodeSession): void;
}
