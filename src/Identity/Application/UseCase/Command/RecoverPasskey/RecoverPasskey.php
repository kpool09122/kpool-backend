<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RecoverPasskey;

use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryNotificationServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySessionStorageServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\SecurityEventRecorderInterface;
use Source\Identity\Application\Service\WebAuthn\RegistrationVerificationInput;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\PasskeyCredentialAlreadyExistsException;
use Source\Identity\Domain\Exception\PasskeyUserNotFoundException;
use Source\Identity\Domain\Factory\PasskeyCredentialFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;

readonly class RecoverPasskey implements RecoverPasskeyInterface
{
    public function __construct(private PasskeyRecoverySessionStorageServiceInterface $sessions, private ChallengeSessionStorageServiceInterface $challenges, private IdentityRepositoryInterface $identityRepository, private PasskeyUserRepositoryInterface $passkeyUserRepository, private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository, private PasskeyCredentialFactoryInterface $factory, private WebAuthnServiceInterface $webAuthn, private AuthServiceInterface $auth, private PasskeyRecoveryNotificationServiceInterface $notification, private SecurityEventRecorderInterface $securityEvents)
    {
    }

    public function process(RecoverPasskeyInputPort $input): void
    {
        $session = $this->sessions->requireValid($input->recoveryKey());
        $challenge = $this->challenges->consumeRecoveryRegistration($input->challengeKey(), $session->identityIdentifier, $input->recoveryKey());
        $identity = $this->identityRepository->findById($session->identityIdentifier) ?? throw new IdentityNotFoundException();
        $user = $this->passkeyUserRepository->findByIdentityIdentifier($session->identityIdentifier) ?? throw new PasskeyUserNotFoundException();
        $verified = $this->webAuthn->verifyRegistration(new RegistrationVerificationInput($input->responseJson(), $challenge->options->json()));
        if ($this->passkeyCredentialRepository->findByCredentialId($verified->credentialId) !== null) {
            throw new PasskeyCredentialAlreadyExistsException();
        }
        $credential = $this->factory->create($user->identifier(), $verified->credentialId, $verified->credentialSource, $verified->signCount, $verified->backupEligible, $verified->backupState, $verified->transports, $input->displayName());
        $this->passkeyCredentialRepository->save($credential);
        $this->passkeyCredentialRepository->deleteAllExcept($session->identityIdentifier, $credential->identifier());
        $this->sessions->consume($input->recoveryKey(), $session->identityIdentifier);
        $this->auth->invalidateAllSessions($session->identityIdentifier);
        $this->notification->notifyCompleted($identity->email(), $identity->language());
        $this->securityEvents->record('passkey.recovery.completed', $session->identityIdentifier, ['method' => $session->method]);
    }
}
