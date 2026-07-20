<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Entity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class Principal
{
    public function __construct(
        private IdentityIdentifier $principalIdentifier,
    ) {
    }

    public function principalIdentifier(): IdentityIdentifier
    {
        return $this->principalIdentifier;
    }
}
