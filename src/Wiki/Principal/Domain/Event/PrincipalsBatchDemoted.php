<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Event;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PrincipalsBatchDemoted
{
    /**
     * @param IdentityIdentifier[] $demotedIdentities
     */
    public function __construct(
        private array $demotedIdentities,
    ) {
    }

    /**
     * @return IdentityIdentifier[]
     */
    public function demotedIdentities(): array
    {
        return $this->demotedIdentities;
    }
}
