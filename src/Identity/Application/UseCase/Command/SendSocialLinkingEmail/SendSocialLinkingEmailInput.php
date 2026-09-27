<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail;

use Source\Shared\Domain\ValueObject\Language;

readonly class SendSocialLinkingEmailInput implements SendSocialLinkingEmailInputPort
{
    public function __construct(private Language $language)
    {
    }

    public function language(): Language
    {
        return $this->language;
    }
}
