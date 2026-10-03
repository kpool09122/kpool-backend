<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Repository;

use Source\Account\Account\Domain\Entity\ArchivedAccount;

interface ArchivedAccountRepositoryInterface
{
    public function save(ArchivedAccount $archivedAccount): void;
}
