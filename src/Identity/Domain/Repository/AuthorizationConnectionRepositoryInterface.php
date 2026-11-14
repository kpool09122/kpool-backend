<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\AuthorizationConnection;

interface AuthorizationConnectionRepositoryInterface
{
    public function save(AuthorizationConnection $authorizationConnection): void;

    public function find(string $identifier): ?AuthorizationConnection;

    public function revoke(string $identifier): void;
}
