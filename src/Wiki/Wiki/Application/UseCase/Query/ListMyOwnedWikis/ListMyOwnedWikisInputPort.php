<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ListMyOwnedWikisInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function accountCategory(): AccountCategory;

    public function perPage(): int;
}
