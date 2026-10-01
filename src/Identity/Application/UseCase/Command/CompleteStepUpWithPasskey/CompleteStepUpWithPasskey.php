<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey;

use DateTimeImmutable;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AuthenticationVerificationInput;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Exception\PasskeyAuthenticationFailedException;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;

readonly class CompleteStepUpWithPasskey implements CompleteStepUpWithPasskeyInterface
{
    private const int AUTHORIZATION_TTL_SECONDS = 600;

    public function __construct(
        private ChallengeSessionStorageServiceInterface $challengeSessionStorageService,
        private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository,
        private PasskeyUserRepositoryInterface $passkeyUserRepository,
        private WebAuthnServiceInterface $webAuthnService,
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorageService,
    ) {
    }

    public function process(CompleteStepUpWithPasskeyInputPort $input, CompleteStepUpWithPasskeyOutputPort $output): void
    {
        $challenge = $this->challengeSessionStorageService->consumeStepUpAuthentication($input->challengeKey(), $input->identityIdentifier());
        $credential = $this->passkeyCredentialRepository->findByCredentialId($input->credentialId());
        if ($credential === null) {
            throw new PasskeyAuthenticationFailedException();
        }
        $user = $this->passkeyUserRepository->findByIdentifier($credential->passkeyUserIdentifier());
        if ($user?->identityIdentifier() === null || (string)$user->identityIdentifier() !== (string)$input->identityIdentifier()) {
            throw new PasskeyAuthenticationFailedException();
        }
        $verified = $this->webAuthnService->verifyAuthentication(new AuthenticationVerificationInput($input->responseJson(), $challenge->options->json(), $credential->credentialSource(), (string)$user->identifier()));
        $now = new DateTimeImmutable();
        $credential->recordAuthentication($verified->credentialSource, $verified->signCount, $verified->backupEligible, $verified->backupState, $now);
        $this->passkeyCredentialRepository->save($credential);
        $this->stepUpAuthenticationStorageService->store(new StepUpAuthentication($input->identityIdentifier(), StepUpAuthenticationMethod::PASSKEY, $now, StepUpAuthenticationScope::RECENT_AUTHENTICATION, $now->modify('+'.self::AUTHORIZATION_TTL_SECONDS.' seconds')));
    }
}
