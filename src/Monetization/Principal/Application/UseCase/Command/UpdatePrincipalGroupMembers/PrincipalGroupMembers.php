<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class PrincipalGroupMembers
{
    /**
     * @param array<int, PrincipalIdentifier> $principalIdentifiers
     */
    public function __construct(
        private PrincipalGroupIdentifier $principalGroupIdentifier,
        private array $principalIdentifiers,
    ) {
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }

    /** @return array<int, PrincipalIdentifier> */
    public function principalIdentifiers(): array
    {
        return $this->principalIdentifiers;
    }
}
