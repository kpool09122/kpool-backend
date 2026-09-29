<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail;

interface VerifyPasskeyRecoveryEmailInterface
{
    public function process(VerifyPasskeyRecoveryEmailInputPort $input, VerifyPasskeyRecoveryEmailOutputPort $output): void;
}
