<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use JsonException;
use Source\Identity\Application\Service\WebAuthn\AuthenticationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\AuthenticationVerificationInput;
use Source\Identity\Application\Service\WebAuthn\RegistrationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\RegistrationVerificationInput;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyAuthentication;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyCredential;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Exception\WebAuthnVerificationException;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
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
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class WebAuthnService implements WebAuthnServiceInterface
{
    private readonly SerializerInterface $serializer;
    private readonly AuthenticatorAttestationResponseValidator $attestationValidator;
    private readonly AuthenticatorAssertionResponseValidator $assertionValidator;

    /** @param string[] $allowedOrigins */
    public function __construct(
        private readonly string $rpId,
        private readonly string $rpName,
        private readonly array $allowedOrigins,
        private readonly int $timeoutMs,
    ) {
        $attestationManager = AttestationStatementSupportManager::create();
        $attestationManager->add(NoneAttestationStatementSupport::create());
        $this->serializer = (new WebauthnSerializerFactory($attestationManager))->create();

        $factory = new CeremonyStepManagerFactory();
        $factory->setAllowedOrigins($allowedOrigins);
        $factory->setCounterChecker(new PasskeyCounterChecker());
        $this->attestationValidator = AuthenticatorAttestationResponseValidator::create($factory->creationCeremony());
        $this->assertionValidator = AuthenticatorAssertionResponseValidator::create($factory->requestCeremony());
    }

    public function createRegistrationOptions(RegistrationOptionsInput $input): WebAuthnOptions
    {
        $excluded = array_map(
            static fn (WebAuthnCredentialId $id): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $id->toBinary(),
            ),
            $input->excludedCredentialIds,
        );
        $selection = AuthenticatorSelectionCriteria::create(
            null,
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
        );
        $options = PublicKeyCredentialCreationOptions::create(
            PublicKeyCredentialRpEntity::create($this->rpName, $this->rpId),
            PublicKeyCredentialUserEntity::create($input->userName, $input->userHandle, $input->userDisplayName),
            $input->challenge->toBinary(),
            authenticatorSelection: $selection,
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $excluded,
            timeout: $this->timeoutMs,
        );

        return new WebAuthnOptions($this->serialize($options));
    }

    public function verifyRegistration(RegistrationVerificationInput $input): VerifiedPasskeyCredential
    {
        try {
            $credential = $this->serializer->deserialize($input->responseJson, PublicKeyCredential::class, 'json');
            $options = $this->serializer->deserialize($input->optionsJson, PublicKeyCredentialCreationOptions::class, 'json');
            if (! $credential instanceof PublicKeyCredential
                || ! $credential->response instanceof AuthenticatorAttestationResponse
                || ! $options instanceof PublicKeyCredentialCreationOptions) {
                throw new WebAuthnVerificationException();
            }
            $record = $this->attestationValidator->check($credential->response, $options, $this->rpId);

            return $this->verifiedCredential($record);
        } catch (WebAuthnVerificationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new WebAuthnVerificationException($exception);
        }
    }

    public function createAuthenticationOptions(AuthenticationOptionsInput $input): WebAuthnOptions
    {
        $allowed = array_map(
            static fn (WebAuthnCredentialId $id): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $id->toBinary(),
            ),
            $input->allowedCredentialIds,
        );
        $options = PublicKeyCredentialRequestOptions::create(
            $input->challenge->toBinary(),
            $this->rpId,
            $allowed,
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            $this->timeoutMs,
        );

        return new WebAuthnOptions($this->serialize($options));
    }

    public function verifyAuthentication(AuthenticationVerificationInput $input): VerifiedPasskeyAuthentication
    {
        try {
            $credential = $this->serializer->deserialize($input->responseJson, PublicKeyCredential::class, 'json');
            $options = $this->serializer->deserialize($input->optionsJson, PublicKeyCredentialRequestOptions::class, 'json');
            $record = $this->serializer->deserialize((string) $input->credentialSource, CredentialRecord::class, 'json');
            if (! $credential instanceof PublicKeyCredential
                || ! $credential->response instanceof AuthenticatorAssertionResponse
                || ! $options instanceof PublicKeyCredentialRequestOptions
                || ! $record instanceof CredentialRecord) {
                throw new WebAuthnVerificationException();
            }
            $verified = $this->assertionValidator->check(
                $record,
                $credential->response,
                $options,
                $this->rpId,
                $input->expectedUserHandle,
            );

            return new VerifiedPasskeyAuthentication(
                new CredentialSource($this->serialize($verified)),
                $verified->counter,
                $verified->backupEligible ?? false,
                $verified->backupStatus ?? false,
            );
        } catch (WebAuthnVerificationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new WebAuthnVerificationException($exception);
        }
    }

    private function verifiedCredential(CredentialRecord $record): VerifiedPasskeyCredential
    {
        return new VerifiedPasskeyCredential(
            WebAuthnCredentialId::fromBinary($record->publicKeyCredentialId),
            new CredentialSource($this->serialize($record)),
            $record->counter,
            $record->backupEligible ?? false,
            $record->backupStatus ?? false,
            $record->transports,
        );
    }

    /** @throws JsonException */
    private function serialize(object $value): string
    {
        return $this->serializer->serialize($value, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
        ]);
    }
}
