<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Domain\Repository;

use Source\Wiki\Shared\Domain\Entity\Actor;
use Source\Wiki\Shared\Domain\ValueObject\ActorIdentifier;

interface ActorRepositoryInterface
{
    public function findById(ActorIdentifier $actorId): ?Actor;

    public function save(Actor $actor): void;
}
