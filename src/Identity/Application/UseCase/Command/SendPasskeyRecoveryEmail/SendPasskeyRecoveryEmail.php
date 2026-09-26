<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail;

use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryEmailVerificationServiceInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;

readonly class SendPasskeyRecoveryEmail implements SendPasskeyRecoveryEmailInterface
{
    public function __construct(private IdentityRepositoryInterface $identityRepository, private PasskeyRecoveryEmailVerificationServiceInterface $verification)
    {
    }

    public function process(SendPasskeyRecoveryEmailInputPort $input): void
    {
        $identity = $this->identityRepository->findByEmail($input->email());
        $this->verification->send($input->email(), $identity?->identityIdentifier(), $input->language());
    }
}
