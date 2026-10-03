<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Event;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class IdentityCreated
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public Email $email,
        public ?string $name,
    ) {
    }
}
