<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\ArchivedIdentity;

interface ArchivedIdentityRepositoryInterface
{
    public function save(ArchivedIdentity $archivedIdentity): void;
}
