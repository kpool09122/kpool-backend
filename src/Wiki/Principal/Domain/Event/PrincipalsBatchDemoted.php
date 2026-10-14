<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Event;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class PrincipalsBatchDemoted
{
    /**
     * @param AccountIdentifier[] $accountIdentifiers
     */
    public function __construct(
        private array $accountIdentifiers,
    ) {
    }

    /**
     * @return AccountIdentifier[]
     */
    public function accountIdentifiers(): array
    {
        return $this->accountIdentifiers;
    }
}
