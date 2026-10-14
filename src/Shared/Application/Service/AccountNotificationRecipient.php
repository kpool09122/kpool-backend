<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

readonly class AccountNotificationRecipient
{
    public function __construct(private Email $email, private Language $language)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function language(): Language
    {
        return $this->language;
    }
}
