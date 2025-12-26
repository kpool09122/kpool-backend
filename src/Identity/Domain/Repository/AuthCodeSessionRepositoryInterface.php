<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Shared\Domain\ValueObject\Email;

interface AuthCodeSessionRepositoryInterface
{
    public function findByEmail(Email $email): ?AuthCodeSession;

    public function save(AuthCodeSession $authCodeSession): void;

    public function delete(Email $email): void;
}
