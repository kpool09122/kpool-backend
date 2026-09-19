<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\UnsignedIntegerObject;
use Source\Identity\Application\Service\WebAuthn\AuthenticationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\AuthenticationVerificationInput;
use Source\Identity\Application\Service\WebAuthn\RegistrationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\RegistrationVerificationInput;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Exception\WebAuthnVerificationException;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Infrastructure\Service\WebAuthnService;
use Tests\TestCase;

class WebAuthnServiceTest extends TestCase
{
    #[\Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('webauthn.rp_id', 'example.com');
        $app['config']->set('webauthn.rp_name', 'k-pool');
        $app['config']->set('webauthn.allowed_origins', ['https://example.com']);
        $app['config']->set('webauthn.timeout_ms', 300000);
    }

    public function testItIsBoundWithoutLeakingLibraryTypesThroughTheInterface(): void
    {
        $service = $this->app->make(WebAuthnServiceInterface::class);

        $this->assertInstanceOf(WebAuthnService::class, $service);
        $interface = new \ReflectionClass(WebAuthnServiceInterface::class);
        foreach ($interface->getMethods() as $method) {
            $types = [(string) $method->getReturnType()];
            foreach ($method->getParameters() as $parameter) {
                $types[] = (string) $parameter->getType();
            }
            $this->assertStringNotContainsString('Webauthn\\', implode(' ', $types));
        }
    }

    public function testRegistrationOptionsRequireDiscoverableCredentialAndUserVerificationWithoutAttachmentRestriction(): void
    {
        $options = $this->service()->createRegistrationOptions(new RegistrationOptionsInput(
            new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
            '01994e3a-a15e-72d3-a456-426614174000',
            'passkey@example.com',
            'k-pool user',
            [],
        ));
        $data = json_decode($options->json(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('example.com', $data['rp']['id']);
        $this->assertSame('required', $data['authenticatorSelection']['residentKey']);
        $this->assertSame('required', $data['authenticatorSelection']['userVerification']);
        $this->assertArrayNotHasKey('authenticatorAttachment', $data['authenticatorSelection']);
        $this->assertSame([], $data['excludeCredentials']);
    }

    public function testAuthenticationOptionsAreDiscoverableAndRequireUserVerification(): void
    {
        $options = $this->service()->createAuthenticationOptions(new AuthenticationOptionsInput(
            new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
        ));
        $data = json_decode($options->json(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('example.com', $data['rpId']);
        $this->assertSame('required', $data['userVerification']);
        $this->assertSame([], $data['allowCredentials']);
    }

    public function testMalformedAttestationIsRejectedByTheLibraryBoundary(): void
    {
        $this->expectException(WebAuthnVerificationException::class);

        $this->service()->verifyRegistration(new RegistrationVerificationInput(
            '{"id":"invalid","type":"public-key","rawId":"invalid","response":{}}',
            $this->service()->createRegistrationOptions(new RegistrationOptionsInput(
                new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
                '01994e3a-a15e-72d3-a456-426614174000',
                'passkey@example.com',
                'k-pool user',
                [],
            ))->json(),
        ));
    }

    public function testAssertionFromAnUntrustedOriginIsRejectedByTheLibrary(): void
    {
        $this->assertAuthenticationRejected(
            'https://evil.example',
            hash('sha256', 'example.com', true),
            'origin',
        );
    }

    public function testAssertionForAnotherRelyingPartyIsRejectedByTheLibrary(): void
    {
        $this->assertAuthenticationRejected(
            'https://example.com',
            hash('sha256', 'another.example', true),
            'rpId hash mismatch',
        );
    }

    public function testAssertionWithAnInvalidSignatureIsRejectedByTheLibrary(): void
    {
        $this->assertAuthenticationRejected(
            'https://example.com',
            hash('sha256', 'example.com', true),
            'signature',
        );
    }

    private function service(): WebAuthnServiceInterface
    {
        return $this->app->make(WebAuthnServiceInterface::class);
    }

    private function assertAuthenticationRejected(string $origin, string $rpIdHash, string $reason): void
    {
        $challenge = new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY');
        $options = $this->service()->createAuthenticationOptions(new AuthenticationOptionsInput($challenge));
        $userHandle = '01994e3a-a15e-72d3-a456-426614174000';
        $credentialId = 'credential-id';
        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => (string) $challenge,
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);
        $authenticatorData = $rpIdHash . chr(0x05) . pack('N', 1);
        $response = json_encode([
            'id' => $this->base64url($credentialId),
            'type' => 'public-key',
            'rawId' => $this->base64url($credentialId),
            'response' => [
                'clientDataJSON' => $this->base64url($clientData),
                'authenticatorData' => $this->base64url($authenticatorData),
                'signature' => $this->base64url("\x30\x06\x02\x01\x01\x02\x01\x01"),
                'userHandle' => $this->base64url($userHandle),
            ],
        ], JSON_THROW_ON_ERROR);

        try {
            $this->service()->verifyAuthentication(new AuthenticationVerificationInput(
                $response,
                $options->json(),
                $this->credentialSource($credentialId, $userHandle),
                $userHandle,
            ));
            $this->fail('Invalid WebAuthn assertion was accepted.');
        } catch (WebAuthnVerificationException $exception) {
            $messages = [];
            for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
                $messages[] = $current->getMessage();
            }
            $this->assertStringContainsStringIgnoringCase($reason, implode(' | ', $messages));
        }
    }

    private function credentialSource(string $credentialId, string $userHandle): CredentialSource
    {
        $privateKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        $this->assertNotFalse($privateKey);
        $details = openssl_pkey_get_details($privateKey);
        $this->assertIsArray($details);
        $this->assertIsArray($details['ec']);
        $publicKey = (string) MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create($details['ec']['x']))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create($details['ec']['y']));

        return new CredentialSource(json_encode([
            'publicKeyCredentialId' => $this->base64url($credentialId),
            'type' => 'public-key',
            'transports' => ['internal'],
            'attestationType' => 'none',
            'trustPath' => [],
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'credentialPublicKey' => $this->base64url($publicKey),
            'userHandle' => $this->base64url($userHandle),
            'counter' => 0,
            'backupEligible' => false,
            'backupStatus' => false,
            'uvInitialized' => true,
        ], JSON_THROW_ON_ERROR));
    }

    private function base64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
