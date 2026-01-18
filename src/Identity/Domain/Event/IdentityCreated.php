<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Event;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class IdentityCreated
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public Email $email,
        public AccountType $accountType,
        public ?string $name,
    ) {
    }
}
