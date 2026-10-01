<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Event;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class IdentityWithdrawing
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
    ) {
    }
}
