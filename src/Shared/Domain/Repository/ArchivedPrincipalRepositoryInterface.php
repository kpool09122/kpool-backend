<?php

declare(strict_types=1);

namespace Source\Shared\Domain\Repository;

use Source\Shared\Domain\Entity\ArchivedPrincipal;

interface ArchivedPrincipalRepositoryInterface
{
    public function save(ArchivedPrincipal $archivedPrincipal): void;
}
