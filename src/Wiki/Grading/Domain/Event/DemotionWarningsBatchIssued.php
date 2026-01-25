<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\Event;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class DemotionWarningsBatchIssued
{
    /**
     * @param IdentityIdentifier[] $warnedIdentities
     */
    public function __construct(
        private array $warnedIdentities,
    ) {
    }

    /**
     * @return IdentityIdentifier[]
     */
    public function warnedIdentities(): array
    {
        return $this->warnedIdentities;
    }
}
