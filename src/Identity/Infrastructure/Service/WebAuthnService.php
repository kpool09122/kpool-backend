<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Source\Identity\Application\Service\VerifiedPasskey;
use Source\Identity\Application\Service\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Exception\InvalidPasskeyException;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

readonly class WebAuthnService implements WebAuthnServiceInterface
{
    private SerializerInterface $serializer;
    private AuthenticatorAttestationResponseValidator $attestationValidator;
    private AuthenticatorAssertionResponseValidator $assertionValidator;

    /** @param string[] $allowedOrigins */
    public function __construct(
        private string $rpId,
        private string $rpName,
        array $allowedOrigins,
    ) {
        $attestationManager = new AttestationStatementSupportManager([
            new NoneAttestationStatementSupport(),
        ]);
        $factory = new CeremonyStepManagerFactory();
        $factory->setAllowedOrigins($allowedOrigins);
        $factory->setAttestationStatementSupportManager($attestationManager);
        $factory->setCounterChecker(new PasskeyCounterChecker());

        $this->serializer = (new WebauthnSerializerFactory($attestationManager))->create();
        $this->attestationValidator = AuthenticatorAttestationResponseValidator::create($factory->creationCeremony());
        $this->assertionValidator = AuthenticatorAssertionResponseValidator::create($factory->requestCeremony());
    }

    public function createRegistrationOptions(
        string $userHandle,
        string $userName,
        string $displayName,
        array $excludedCredentialIds = [],
    ): WebAuthnOptions {
        $options = PublicKeyCredentialCreationOptions::create(
            PublicKeyCredentialRpEntity::create($this->rpName, $this->rpId),
            PublicKeyCredentialUserEntity::create($userName, $userHandle, $displayName),
            random_bytes(32),
            [
                PublicKeyCredentialParameters::createPk(-7),
                PublicKeyCredentialParameters::createPk(-257),
            ],
            AuthenticatorSelectionCriteria::create(
                authenticatorAttachment: null,
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            array_map(
                static fn (string $credentialId): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                    PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                    $credentialId,
                ),
                $excludedCredentialIds,
            ),
            300000,
        );

        return $this->toOptions($options);
    }

    public function verifyRegistration(string $credentialJson, string $optionsJson): VerifiedPasskey
    {
        try {
            $credential = $this->deserializeCredential($credentialJson);
            if (! $credential->response instanceof AuthenticatorAttestationResponse) {
                throw new InvalidPasskeyException('Attestation responseが必要です');
            }
            $options = $this->serializer->deserialize($optionsJson, PublicKeyCredentialCreationOptions::class, 'json');
            if (! $options instanceof PublicKeyCredentialCreationOptions) {
                throw new InvalidPasskeyException('登録オプションが不正です');
            }
            $record = $this->attestationValidator->check($credential->response, $options, $this->rpId);

            return $this->verified($record);
        } catch (InvalidPasskeyException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidPasskeyException('パスキー登録の検証に失敗しました', previous: $exception);
        }
    }

    public function createAuthenticationOptions(): WebAuthnOptions
    {
        return $this->toOptions(PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            $this->rpId,
            [],
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            300000,
        ));
    }

    public function credentialIdFromResponse(string $credentialJson): string
    {
        try {
            return $this->deserializeCredential($credentialJson)->rawId;
        } catch (Throwable $exception) {
            throw new InvalidPasskeyException('credential IDを読み取れません', previous: $exception);
        }
    }

    public function verifyAuthentication(
        string $credentialJson,
        string $optionsJson,
        string $credentialSource,
    ): VerifiedPasskey {
        try {
            $credential = $this->deserializeCredential($credentialJson);
            if (! $credential->response instanceof AuthenticatorAssertionResponse) {
                throw new InvalidPasskeyException('Assertion responseが必要です');
            }
            $options = $this->serializer->deserialize($optionsJson, PublicKeyCredentialRequestOptions::class, 'json');
            $record = $this->serializer->deserialize($credentialSource, CredentialRecord::class, 'json');
            if (! $options instanceof PublicKeyCredentialRequestOptions || ! $record instanceof CredentialRecord) {
                throw new InvalidPasskeyException('認証データが不正です');
            }
            if (! hash_equals($record->publicKeyCredentialId, $credential->rawId)) {
                throw new InvalidPasskeyException('credential IDが一致しません');
            }
            $verified = $this->assertionValidator->check(
                $record,
                $credential->response,
                $options,
                $this->rpId,
                $record->userHandle,
            );

            return $this->verified($verified);
        } catch (InvalidPasskeyException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidPasskeyException('パスキー認証の検証に失敗しました', previous: $exception);
        }
    }

    private function deserializeCredential(string $json): PublicKeyCredential
    {
        $credential = $this->serializer->deserialize($json, PublicKeyCredential::class, 'json');
        if (! $credential instanceof PublicKeyCredential) {
            throw new InvalidPasskeyException('パスキー応答が不正です');
        }

        return $credential;
    }

    private function toOptions(PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options): WebAuthnOptions
    {
        $json = $this->serializer->serialize($options, 'json');
        /** @var array<string, mixed> $array */
        $array = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return new WebAuthnOptions($json, $array);
    }

    private function verified(CredentialRecord $record): VerifiedPasskey
    {
        return new VerifiedPasskey(
            $record->publicKeyCredentialId,
            $this->serializer->serialize($record, 'json'),
            $record->counter,
            $record->backupEligible ?? false,
            $record->backupStatus ?? false,
            $record->transports,
        );
    }
}
