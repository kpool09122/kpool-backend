<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail;

interface SendSocialLinkingEmailOutputPort
{
    public function setAccepted(bool $accepted): void;
}
