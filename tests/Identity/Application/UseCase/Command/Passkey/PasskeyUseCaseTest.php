<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\Passkey;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\VerifiedPasskey;
use Source\Identity\Application\Service\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\Passkey\PasskeyUseCase;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Entity\PasskeyChallengeSession;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Identity\Domain\Exception\LastAuthenticationMethodException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyChallengeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class PasskeyUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testBeginSignupRequiresVerifiedEmailAndStoresAssociatedChallenge(): void
    {
        $dependencies = $this->dependencies();
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $challengeIdentifier = StrTestHelper::generateUuid();
        $email = new Email('passkey@example.com');
        $authCodeSession = new AuthCodeSession($email, new AuthCode('123456'), new DateTimeImmutable(), new DateTimeImmutable());
        $options = new WebAuthnOptions('{"challenge":"encoded"}', ['challenge' => 'encoded']);

        $dependencies['identities']->shouldReceive('findByEmail')->once()->with(Mockery::on(fn (Email $value): bool => (string) $value === (string) $email))->andReturnNull();
        $dependencies['authCodeSessions']->shouldReceive('findByEmail')->once()->andReturn($authCodeSession);
        $dependencies['uuidGenerator']->shouldReceive('generate')->twice()->andReturn((string) $identityIdentifier, $challengeIdentifier);
        $dependencies['webAuthn']->shouldReceive('createRegistrationOptions')->once()->with((string) $identityIdentifier, (string) $email, 'Passkey User')->andReturn($options);
        $dependencies['challenges']->shouldReceive('save')->once()->with(Mockery::on(
            fn (PasskeyChallengeSession $session): bool => $session->identifier() === $challengeIdentifier
                && $session->purpose() === PasskeyChallengeSession::PURPOSE_SIGNUP
                && (string) $session->identityIdentifier() === (string) $identityIdentifier
                && $session->signupData()['email'] === (string) $email,
        ));

        $result = $this->useCase($dependencies)->beginSignup('Passkey User', (string) $email, 'ja', null, null);

        $this->assertSame($challengeIdentifier, $result['challengeIdentifier']);
        $this->assertSame(['challenge' => 'encoded'], $result['publicKey']);
    }

    public function testFinishSignupCreatesIdentityAndFirstPasskeyThenLogsIn(): void
    {
        $dependencies = $this->dependencies();
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $challengeIdentifier = StrTestHelper::generateUuid();
        $email = new Email('new-passkey@example.com');
        $session = new PasskeyChallengeSession(
            $challengeIdentifier,
            PasskeyChallengeSession::PURPOSE_SIGNUP,
            '{"challenge":"encoded"}',
            $identityIdentifier,
            ['identity_name' => 'New User', 'email' => (string) $email, 'language' => 'ja', 'base64_encoded_image' => null, 'one_time_token' => null],
        );
        $verifiedAt = new DateTimeImmutable();
        $authCodeSession = new AuthCodeSession($email, new AuthCode('123456'), new DateTimeImmutable(), $verifiedAt);
        $identity = $this->identity($identityIdentifier, $email);
        $verified = new VerifiedPasskey('credential-id', '{"source":true}', 0, true, true, ['internal']);

        $dependencies['challenges']->shouldReceive('consume')->once()->with($challengeIdentifier, PasskeyChallengeSession::PURPOSE_SIGNUP)->andReturn($session);
        $dependencies['identities']->shouldReceive('findByEmail')->once()->andReturnNull();
        $dependencies['webAuthn']->shouldReceive('verifyRegistration')->once()->andReturn($verified);
        $dependencies['passkeys']->shouldReceive('findByCredentialId')->once()->with('credential-id')->andReturnNull();
        $dependencies['identityFactory']->shouldReceive('create')->once()->andReturn($identity);
        $dependencies['authCodeSessions']->shouldReceive('findByEmail')->once()->andReturn($authCodeSession);
        $dependencies['uuidGenerator']->shouldReceive('generate')->once()->andReturn(StrTestHelper::generateUuid());
        $dependencies['identities']->shouldReceive('save')->once()->with($identity);
        $dependencies['passkeys']->shouldReceive('save')->once()->with(Mockery::on(
            fn (PasskeyCredential $passkey): bool => (string) $passkey->identityIdentifier() === (string) $identityIdentifier
                && $passkey->credentialId() === 'credential-id'
                && $passkey->backupEligible()
                && $passkey->backupState(),
        ));
        $dependencies['authCodeSessions']->shouldReceive('delete')->once()->with(Mockery::on(fn (Email $value): bool => (string) $value === (string) $email));
        $dependencies['events']->shouldReceive('dispatch')->once()->with(Mockery::type(IdentityCreated::class));
        $dependencies['authService']->shouldReceive('login')->once()->with($identity);

        $result = $this->useCase($dependencies)->finishSignup($challengeIdentifier, ['id' => 'credential'], 'Phone');

        $this->assertSame((string) $identityIdentifier, $result['identityIdentifier']);
        $this->assertSame($verifiedAt, $identity->emailVerifiedAt());
    }

    public function testFinishLoginUsesDiscoverableCredentialAndUpdatesAuthenticationState(): void
    {
        $dependencies = $this->dependencies();
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identity = $this->identity($identityIdentifier, new Email('login@example.com'));
        $passkey = $this->passkey($identityIdentifier, false, false, 4);
        $challengeIdentifier = StrTestHelper::generateUuid();
        $session = new PasskeyChallengeSession($challengeIdentifier, PasskeyChallengeSession::PURPOSE_LOGIN, '{"challenge":"encoded"}');
        $verified = new VerifiedPasskey($passkey->credentialId(), '{"updated":true}', 5, false, false, ['usb']);

        $dependencies['challenges']->shouldReceive('consume')->once()->andReturn($session);
        $dependencies['webAuthn']->shouldReceive('credentialIdFromResponse')->once()->andReturn($passkey->credentialId());
        $dependencies['passkeys']->shouldReceive('findByCredentialId')->once()->with($passkey->credentialId())->andReturn($passkey);
        $dependencies['webAuthn']->shouldReceive('verifyAuthentication')->once()->andReturn($verified);
        $dependencies['identities']->shouldReceive('findById')->once()->with($identityIdentifier)->andReturn($identity);
        $dependencies['passkeys']->shouldReceive('save')->once()->with($passkey);
        $dependencies['authService']->shouldReceive('login')->once()->with($identity);

        $result = $this->useCase($dependencies)->finishLogin($challengeIdentifier, ['id' => 'credential']);

        $this->assertSame((string) $identityIdentifier, $result['identityIdentifier']);
        $this->assertSame(5, $passkey->signCount());
        $this->assertSame('{"updated":true}', $passkey->credentialSource());
        $this->assertNotNull($passkey->lastUsedAt());
    }

    public function testFinalAuthenticationMethodCannotBeDeleted(): void
    {
        $dependencies = $this->dependencies();
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identity = $this->identity($identityIdentifier, new Email('last@example.com'));
        $passkey = $this->passkey($identityIdentifier);

        $dependencies['identities']->shouldReceive('findById')->once()->andReturn($identity);
        $dependencies['passkeys']->shouldReceive('findByIdentifier')->once()->with($passkey->identifier())->andReturn($passkey);
        $dependencies['passkeys']->shouldReceive('findByIdentityForUpdate')->once()->andReturn([$passkey]);
        $dependencies['passkeys']->shouldNotReceive('delete');
        $this->expectException(LastAuthenticationMethodException::class);

        $this->useCase($dependencies)->delete($identityIdentifier, $passkey->identifier());
    }

    /** @return array<string, MockInterface> */
    private function dependencies(): array
    {
        return [
            'webAuthn' => Mockery::mock(WebAuthnServiceInterface::class),
            'challenges' => Mockery::mock(PasskeyChallengeSessionRepositoryInterface::class),
            'passkeys' => Mockery::mock(PasskeyCredentialRepositoryInterface::class),
            'identities' => Mockery::mock(IdentityRepositoryInterface::class),
            'identityFactory' => Mockery::mock(IdentityFactoryInterface::class),
            'authCodeSessions' => Mockery::mock(AuthCodeSessionRepositoryInterface::class),
            'authService' => Mockery::mock(AuthServiceInterface::class),
            'events' => Mockery::mock(EventDispatcherInterface::class),
            'images' => Mockery::mock(ImageServiceInterface::class),
            'uuidGenerator' => Mockery::mock(UuidGeneratorInterface::class),
        ];
    }

    /** @param array<string, MockInterface> $dependencies */
    private function useCase(array $dependencies): PasskeyUseCase
    {
        /** @var WebAuthnServiceInterface&MockInterface $webAuthn */
        $webAuthn = $dependencies['webAuthn'];
        /** @var PasskeyChallengeSessionRepositoryInterface&MockInterface $challenges */
        $challenges = $dependencies['challenges'];
        /** @var PasskeyCredentialRepositoryInterface&MockInterface $passkeys */
        $passkeys = $dependencies['passkeys'];
        /** @var IdentityRepositoryInterface&MockInterface $identities */
        $identities = $dependencies['identities'];
        /** @var IdentityFactoryInterface&MockInterface $identityFactory */
        $identityFactory = $dependencies['identityFactory'];
        /** @var AuthCodeSessionRepositoryInterface&MockInterface $authCodeSessions */
        $authCodeSessions = $dependencies['authCodeSessions'];
        /** @var AuthServiceInterface&MockInterface $authService */
        $authService = $dependencies['authService'];
        /** @var EventDispatcherInterface&MockInterface $events */
        $events = $dependencies['events'];
        /** @var ImageServiceInterface&MockInterface $images */
        $images = $dependencies['images'];
        /** @var UuidGeneratorInterface&MockInterface $uuidGenerator */
        $uuidGenerator = $dependencies['uuidGenerator'];

        return new PasskeyUseCase(
            $webAuthn,
            $challenges,
            $passkeys,
            $identities,
            $identityFactory,
            $authCodeSessions,
            $authService,
            $events,
            $images,
            $uuidGenerator,
        );
    }

    private function identity(IdentityIdentifier $identifier, Email $email): Identity
    {
        return new Identity($identifier, new IdentityName('Passkey User'), $email, Language::JAPANESE, null, null);
    }

    private function passkey(
        IdentityIdentifier $identityIdentifier,
        bool $backupEligible = false,
        bool $backupState = false,
        int $signCount = 0,
    ): PasskeyCredential {
        return new PasskeyCredential(
            StrTestHelper::generateUuid(),
            $identityIdentifier,
            'credential-id',
            '{"source":true}',
            $signCount,
            $backupEligible,
            $backupState,
            ['internal'],
            'Phone',
        );
    }
}
