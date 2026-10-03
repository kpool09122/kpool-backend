<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartStepUpWithSocial;

interface StartStepUpWithSocialOutputPort
{
    public function setRedirectUrl(string $redirectUrl): void;
}
