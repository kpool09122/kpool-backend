<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PasskeyUserRepositoryInterface
{
    public function save(PasskeyUser $user): void;

    public function findByIdentifier(PasskeyUserIdentifier $identifier): ?PasskeyUser;

    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): ?PasskeyUser;
}
