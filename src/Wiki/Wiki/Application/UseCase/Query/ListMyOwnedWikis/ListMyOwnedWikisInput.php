<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class ListMyOwnedWikisInput implements ListMyOwnedWikisInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private AccountCategory $accountCategory,
        private ?int $perPage = null,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function accountCategory(): AccountCategory
    {
        return $this->accountCategory;
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }
}
