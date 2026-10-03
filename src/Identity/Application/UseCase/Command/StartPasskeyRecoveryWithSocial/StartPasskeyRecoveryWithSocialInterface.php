<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

interface StartPasskeyRecoveryWithSocialInterface
{
    public function process(
        StartPasskeyRecoveryWithSocialInputPort $input,
        StartPasskeyRecoveryWithSocialOutputPort $output,
    ): void;
}
