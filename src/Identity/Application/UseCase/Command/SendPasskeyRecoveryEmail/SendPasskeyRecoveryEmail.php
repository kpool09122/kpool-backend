<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail;

use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryEmailVerificationServiceInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;

readonly class SendPasskeyRecoveryEmail implements SendPasskeyRecoveryEmailInterface
{
    public function __construct(private IdentityRepositoryInterface $identityRepository, private PasskeyRecoveryEmailVerificationServiceInterface $passkeyRecoveryEmailVerificationService)
    {
    }

    public function process(SendPasskeyRecoveryEmailInputPort $input, SendPasskeyRecoveryEmailOutputPort $output): void
    {
        $identity = $this->identityRepository->findByEmail($input->email());
        $output->setStatus($this->passkeyRecoveryEmailVerificationService->send($input->email(), $identity?->identityIdentifier(), $input->language()));
    }
}
