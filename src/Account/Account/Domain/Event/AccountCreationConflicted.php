<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Event;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

readonly class AccountCreationConflicted
{
    public function __construct(
        public Email $email,
        public Language $language,
    ) {
    }
}
