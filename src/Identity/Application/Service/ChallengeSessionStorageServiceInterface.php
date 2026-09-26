<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Application\Service\WebAuthn\AdditionChallenge;
use Source\Identity\Application\Service\WebAuthn\AuthenticationChallenge;
use Source\Identity\Application\Service\WebAuthn\RecoveryRegistrationChallenge;
use Source\Identity\Application\Service\WebAuthn\RegistrationChallenge;
use Source\Identity\Application\Service\WebAuthn\StepUpAuthenticationChallenge;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ChallengeSessionStorageServiceInterface
{
    public function storeRegistration(RegistrationChallenge $challenge): void;

    public function consumeRegistration(ChallengeSessionKey $key): RegistrationChallenge;

    public function storeAuthentication(AuthenticationChallenge $challenge): void;

    public function consumeAuthentication(ChallengeSessionKey $key): AuthenticationChallenge;

    public function storeAddition(AdditionChallenge $challenge): void;

    public function consumeAddition(
        ChallengeSessionKey $key,
        IdentityIdentifier $expectedIdentityIdentifier,
    ): AdditionChallenge;

    public function storeStepUpAuthentication(StepUpAuthenticationChallenge $challenge): void;

    public function consumeStepUpAuthentication(
        ChallengeSessionKey $key,
        IdentityIdentifier $expectedIdentityIdentifier,
    ): StepUpAuthenticationChallenge;

    public function storeRecoveryRegistration(RecoveryRegistrationChallenge $challenge): void;

    public function consumeRecoveryRegistration(
        ChallengeSessionKey $key,
        IdentityIdentifier $expectedIdentityIdentifier,
        PasskeyRecoveryKey $expectedRecoveryKey,
    ): RecoveryRegistrationChallenge;
}
