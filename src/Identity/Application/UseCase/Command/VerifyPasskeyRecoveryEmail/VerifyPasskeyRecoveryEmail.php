<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail;

use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryEmailVerificationServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySessionStorageServiceInterface;

readonly class VerifyPasskeyRecoveryEmail implements VerifyPasskeyRecoveryEmailInterface
{
    public function __construct(private PasskeyRecoveryEmailVerificationServiceInterface $passkeyRecoveryEmailVerificationService, private PasskeyRecoverySessionStorageServiceInterface $passkeyRecoverySessionStorageService)
    {
    }

    public function process(VerifyPasskeyRecoveryEmailInputPort $input, VerifyPasskeyRecoveryEmailOutputPort $output): void
    {
        $identityIdentifier = $this->passkeyRecoveryEmailVerificationService->verify($input->email(), $input->code());
        $output->setRecoveryKey($this->passkeyRecoverySessionStorageService->issue($identityIdentifier, 'email'));
    }
}
