<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Event;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

readonly class AccountCreated
{
    public function __construct(
        public AccountIdentifier $accountIdentifier,
        public Email $email,
        public ?IdentityIdentifier $identityIdentifier,
        public Language $language,
    ) {
    }
}
