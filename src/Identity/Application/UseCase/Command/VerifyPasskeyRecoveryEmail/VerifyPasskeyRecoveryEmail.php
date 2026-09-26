<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail;

use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryEmailVerificationServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySessionStorageServiceInterface;

readonly class VerifyPasskeyRecoveryEmail implements VerifyPasskeyRecoveryEmailInterface
{
    public function __construct(private PasskeyRecoveryEmailVerificationServiceInterface $verification, private PasskeyRecoverySessionStorageServiceInterface $sessions)
    {
    }

    public function process(VerifyPasskeyRecoveryEmailInputPort $input, VerifyPasskeyRecoveryEmailOutputPort $output): void
    {
        $identityIdentifier = $this->verification->verify($input->email(), $input->code());
        $output->setRecoveryKey($this->sessions->issue($identityIdentifier, 'email'));
    }
}
