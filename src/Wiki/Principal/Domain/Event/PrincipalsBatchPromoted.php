<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Event;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PrincipalsBatchPromoted
{
    /**
     * @param IdentityIdentifier[] $promotedIdentities
     */
    public function __construct(
        private array $promotedIdentities,
    ) {
    }

    /**
     * @return IdentityIdentifier[]
     */
    public function promotedIdentities(): array
    {
        return $this->promotedIdentities;
    }
}
