<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AuthorizationConnectionRepositoryInterface
{
    public function save(AuthorizationConnection $authorizationConnection): void;

    public function find(AuthorizationConnectionIdentifier $identifier): ?AuthorizationConnection;

    /** @return list<AuthorizationConnection> */
    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array;
}
