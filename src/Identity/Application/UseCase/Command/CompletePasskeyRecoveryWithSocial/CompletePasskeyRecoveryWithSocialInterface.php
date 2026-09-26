<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial;

interface CompletePasskeyRecoveryWithSocialInterface
{
    public function process(
        CompletePasskeyRecoveryWithSocialInputPort $input,
        CompletePasskeyRecoveryWithSocialOutputPort $output,
    ): void;
}
