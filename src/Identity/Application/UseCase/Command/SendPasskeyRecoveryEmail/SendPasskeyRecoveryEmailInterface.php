<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail;

interface SendPasskeyRecoveryEmailInterface
{
    public function process(SendPasskeyRecoveryEmailInputPort $input): void;
}
