<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\Event;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class DemotionWarningsBatchIssued
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
