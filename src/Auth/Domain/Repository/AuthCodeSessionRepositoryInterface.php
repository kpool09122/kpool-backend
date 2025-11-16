<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Repository;

use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Shared\Domain\ValueObject\Email;

interface AuthCodeSessionRepositoryInterface
{
    public function findByEmail(Email $email): ?AuthCodeSession;

    public function save(AuthCodeSession $authCodeSession): void;

    public function delete(Email $email): void;
}
