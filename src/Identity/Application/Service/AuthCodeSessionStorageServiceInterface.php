<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Domain\ValueObject\AuthCodeSession;
use Source\Shared\Domain\ValueObject\Email;

interface AuthCodeSessionStorageServiceInterface
{
    public function findByEmail(Email $email): ?AuthCodeSession;

    public function save(AuthCodeSession $authCodeSession): void;

    public function delete(Email $email): void;
}
