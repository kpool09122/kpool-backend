<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Passkey;

use DateTimeImmutable;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Entity\PasskeyChallengeSession;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidPasskeyException;
use Source\Identity\Domain\Exception\LastAuthenticationMethodException;
use Source\Identity\Domain\Exception\PasskeyNotFoundException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyChallengeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\OneTimeToken;

readonly class PasskeyUseCase implements PasskeyUseCaseInterface
{
    public function __construct(
        private WebAuthnServiceInterface $webAuthn,
        private PasskeyChallengeSessionRepositoryInterface $challenges,
        private PasskeyCredentialRepositoryInterface $passkeys,
        private IdentityRepositoryInterface $identities,
        private IdentityFactoryInterface $identityFactory,
        private AuthCodeSessionRepositoryInterface $authCodeSessions,
        private AuthServiceInterface $authService,
        private EventDispatcherInterface $events,
        private ImageServiceInterface $images,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function beginSignup(string $identityName, string $email, string $language, ?string $base64EncodedImage, ?string $oneTimeToken): array
    {
        $emailValue = new Email($email);
        if ($this->identities->findByEmail($emailValue) !== null) {
            throw new AlreadyUserExistsException();
        }
        if ($oneTimeToken === null) {
            $authCodeSession = $this->authCodeSessions->findByEmail($emailValue);
            if ($authCodeSession === null || $authCodeSession->verifiedAt() === null) {
                throw new AuthCodeSessionNotFoundException();
            }
        }
        $identityIdentifier = new IdentityIdentifier($this->uuidGenerator->generate());
        $options = $this->webAuthn->createRegistrationOptions(
            (string) $identityIdentifier,
            $email,
            $identityName,
        );
        $challengeIdentifier = $this->uuidGenerator->generate();
        $this->challenges->save(new PasskeyChallengeSession(
            $challengeIdentifier,
            PasskeyChallengeSession::PURPOSE_SIGNUP,
            $options->optionsJson(),
            $identityIdentifier,
            [
                'identity_name' => (string) new IdentityName($identityName),
                'email' => (string) $emailValue,
                'language' => Language::from($language)->value,
                'base64_encoded_image' => $base64EncodedImage,
                'one_time_token' => $oneTimeToken,
            ],
        ));

        return ['challengeIdentifier' => $challengeIdentifier, 'publicKey' => $options->options()];
    }

    /** @return array<string, mixed> */
    public function finishSignup(string $challengeIdentifier, array $credential, string $displayName): array
    {
        $session = $this->challenges->consume($challengeIdentifier, PasskeyChallengeSession::PURPOSE_SIGNUP);
        $signup = $session->signupData();
        $identityIdentifier = $session->identityIdentifier();
        if ($signup === null || $identityIdentifier === null) {
            throw new InvalidPasskeyException('登録セッションが不正です');
        }
        $email = new Email((string) $signup['email']);
        if ($this->identities->findByEmail($email) !== null) {
            throw new AlreadyUserExistsException();
        }
        $verified = $this->webAuthn->verifyRegistration(json_encode($credential, JSON_THROW_ON_ERROR), $session->optionsJson());
        if ($this->passkeys->findByCredentialId($verified->credentialId()) !== null) {
            throw new InvalidPasskeyException('このパスキーは既に登録されています');
        }
        $identity = $this->identityFactory->create(
            new IdentityName((string) $signup['identity_name']),
            $email,
            Language::from((string) $signup['language']),
            identityIdentifier: $identityIdentifier,
        );
        $oneTimeToken = isset($signup['one_time_token'])
            ? new OneTimeToken((string) $signup['one_time_token'])
            : null;
        if ($oneTimeToken === null) {
            $authSession = $this->authCodeSessions->findByEmail($email);
            if ($authSession === null) {
                throw new AuthCodeSessionNotFoundException();
            }
            $identity->copyEmailVerifiedAt($authSession);
        } else {
            $identity->markEmailVerified(new DateTimeImmutable());
        }
        if (isset($signup['base64_encoded_image']) && is_string($signup['base64_encoded_image'])) {
            $identity->setProfileImage($this->images->upload($signup['base64_encoded_image']));
        }
        $passkey = new PasskeyCredential(
            $this->uuidGenerator->generate(),
            $identityIdentifier,
            $verified->credentialId(),
            $verified->credentialSource(),
            $verified->signCount(),
            $verified->backupEligible(),
            $verified->backupState(),
            $verified->transports(),
            $displayName,
        );
        $this->identities->save($identity);
        $this->passkeys->save($passkey);
        $this->authCodeSessions->delete($email);
        if ($oneTimeToken !== null) {
            $this->events->dispatch(new IdentityCreatedViaInvitation($identityIdentifier, $oneTimeToken));
        } else {
            $this->events->dispatch(new IdentityCreated(
                $identityIdentifier,
                $email,
                AccountType::INDIVIDUAL,
                (string) $identity->identityName(),
            ));
        }
        $this->authService->login($identity);

        return $this->identityArray($identity);
    }

    public function beginLogin(): array
    {
        $options = $this->webAuthn->createAuthenticationOptions();
        $challengeIdentifier = $this->uuidGenerator->generate();
        $this->challenges->save(new PasskeyChallengeSession(
            $challengeIdentifier,
            PasskeyChallengeSession::PURPOSE_LOGIN,
            $options->optionsJson(),
        ));

        return ['challengeIdentifier' => $challengeIdentifier, 'publicKey' => $options->options()];
    }

    /** @return array<string, mixed> */
    public function finishLogin(string $challengeIdentifier, array $credential): array
    {
        $session = $this->challenges->consume($challengeIdentifier, PasskeyChallengeSession::PURPOSE_LOGIN);
        $credentialJson = json_encode($credential, JSON_THROW_ON_ERROR);
        $passkey = $this->passkeys->findByCredentialId($this->webAuthn->credentialIdFromResponse($credentialJson));
        if ($passkey === null) {
            throw new PasskeyNotFoundException();
        }
        $verified = $this->webAuthn->verifyAuthentication($credentialJson, $session->optionsJson(), $passkey->credentialSource());
        $passkey->recordAuthentication(
            $verified->credentialSource(),
            $verified->signCount(),
            $verified->backupEligible(),
            $verified->backupState(),
            new DateTimeImmutable(),
        );
        $identity = $this->identities->findById($passkey->identityIdentifier());
        if ($identity === null || $identity->isDelegatedIdentity()) {
            throw new IdentityNotFoundException();
        }
        $this->passkeys->save($passkey);
        $this->authService->login($identity);

        return $this->identityArray($identity);
    }

    public function beginAdd(IdentityIdentifier $identityIdentifier): array
    {
        $identity = $this->identity($identityIdentifier);
        if ($identity->isDelegatedIdentity()) {
            throw new InvalidPasskeyException('委譲Identityへ認証資格情報は追加できません');
        }
        $existing = $this->passkeys->findByIdentity($identityIdentifier);
        $options = $this->webAuthn->createRegistrationOptions(
            (string) $identityIdentifier,
            (string) $identity->email(),
            (string) $identity->identityName(),
            array_map(static fn (PasskeyCredential $passkey): string => $passkey->credentialId(), $existing),
        );
        $challengeIdentifier = $this->uuidGenerator->generate();
        $this->challenges->save(new PasskeyChallengeSession(
            $challengeIdentifier,
            PasskeyChallengeSession::PURPOSE_ADD,
            $options->optionsJson(),
            $identityIdentifier,
        ));

        return ['challengeIdentifier' => $challengeIdentifier, 'publicKey' => $options->options()];
    }

    /** @return array<string, mixed> */
    public function finishAdd(IdentityIdentifier $identityIdentifier, string $challengeIdentifier, array $credential, string $displayName): array
    {
        $session = $this->challenges->consume($challengeIdentifier, PasskeyChallengeSession::PURPOSE_ADD);
        if ($session->identityIdentifier() === null || (string) $session->identityIdentifier() !== (string) $identityIdentifier) {
            throw new InvalidPasskeyException('チャレンジのIdentityが一致しません');
        }
        $this->identity($identityIdentifier);
        $verified = $this->webAuthn->verifyRegistration(json_encode($credential, JSON_THROW_ON_ERROR), $session->optionsJson());
        if ($this->passkeys->findByCredentialId($verified->credentialId()) !== null) {
            throw new InvalidPasskeyException('このパスキーは既に登録されています');
        }
        $passkey = new PasskeyCredential(
            $this->uuidGenerator->generate(),
            $identityIdentifier,
            $verified->credentialId(),
            $verified->credentialSource(),
            $verified->signCount(),
            $verified->backupEligible(),
            $verified->backupState(),
            $verified->transports(),
            $displayName,
        );
        $this->passkeys->save($passkey);

        return $this->passkeyArray($passkey);
    }

    public function list(IdentityIdentifier $identityIdentifier): array
    {
        $this->identity($identityIdentifier);

        return array_map(fn (PasskeyCredential $passkey): array => $this->passkeyArray($passkey), $this->passkeys->findByIdentity($identityIdentifier));
    }

    public function rename(IdentityIdentifier $identityIdentifier, string $passkeyIdentifier, string $displayName): void
    {
        $passkey = $this->ownedPasskey($identityIdentifier, $passkeyIdentifier);
        $passkey->rename($displayName);
        $this->passkeys->save($passkey);
    }

    public function delete(IdentityIdentifier $identityIdentifier, string $passkeyIdentifier): void
    {
        $identity = $this->identity($identityIdentifier);
        $passkey = $this->ownedPasskey($identityIdentifier, $passkeyIdentifier);
        if (! $identity->isDelegatedIdentity()
            && count($this->passkeys->findByIdentityForUpdate($identityIdentifier)) <= 1
            && $identity->socialConnections() === []) {
            throw new LastAuthenticationMethodException('最後の認証手段は削除できません');
        }
        $this->passkeys->delete($passkey);
    }

    private function identity(IdentityIdentifier $identifier): \Source\Identity\Domain\Entity\Identity
    {
        return $this->identities->findById($identifier) ?? throw new IdentityNotFoundException();
    }

    private function ownedPasskey(IdentityIdentifier $identityIdentifier, string $passkeyIdentifier): PasskeyCredential
    {
        $passkey = $this->passkeys->findByIdentifier($passkeyIdentifier);
        if ($passkey === null || (string) $passkey->identityIdentifier() !== (string) $identityIdentifier) {
            throw new PasskeyNotFoundException();
        }

        return $passkey;
    }

    /** @return array<string, mixed> */
    private function passkeyArray(PasskeyCredential $passkey): array
    {
        return [
            'passkeyIdentifier' => $passkey->identifier(),
            'displayName' => $passkey->displayName(),
            'transports' => $passkey->transports(),
            'backupEligible' => $passkey->backupEligible(),
            'backupState' => $passkey->backupState(),
            'lastUsedAt' => $passkey->lastUsedAt()?->format(DATE_ATOM),
            'createdAt' => $passkey->createdAt()?->format(DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function identityArray(\Source\Identity\Domain\Entity\Identity $identity): array
    {
        return [
            'identityIdentifier' => (string) $identity->identityIdentifier(),
            'identityName' => (string) $identity->identityName(),
            'email' => (string) $identity->email(),
            'language' => $identity->language()->value,
            'profileImage' => $identity->profileImage() === null ? null : (string) $identity->profileImage(),
        ];
    }
}
