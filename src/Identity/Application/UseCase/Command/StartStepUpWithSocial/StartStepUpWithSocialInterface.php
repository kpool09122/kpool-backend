<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartStepUpWithSocial;

use Source\Identity\Domain\Exception\StepUpSocialAuthenticationFailedException;

interface StartStepUpWithSocialInterface
{
    /** @throws StepUpSocialAuthenticationFailedException */
    public function process(StartStepUpWithSocialInputPort $input, StartStepUpWithSocialOutputPort $output): void;
}
