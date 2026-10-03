<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail;

use Source\Shared\Domain\ValueObject\Language;

interface SendSocialLinkingEmailInputPort
{
    public function language(): Language;
}
