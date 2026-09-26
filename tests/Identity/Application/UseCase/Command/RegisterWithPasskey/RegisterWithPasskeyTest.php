<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\RegisterWithPasskey;

use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\SignupInvitationValidatorInterface;
use Source\Identity\Application\Service\WebAuthn\RegistrationChallenge;
use Source\Identity\Application\Service\WebAuthn\RegistrationVerificationInput;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyCredential;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyInput;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyInterface;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\InvalidSignupInvitationException;
use Source\Identity\Domain\Exception\PasskeyCredentialAlreadyExistsException;
use Source\Identity\Domain\Exception\PasskeyUserAlreadyLinkedException;
use Source\Identity\Domain\Exception\PasskeyUserNotFoundException;
use Source\Identity\Domain\Exception\WebAuthnVerificationException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Factory\PasskeyCredentialFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Tests\TestCase;

class RegisterWithPasskeyTest extends TestCase
{
    #[DataProvider('signupSessionProvider')]
    public function testItCreatesAndAuthenticatesIdentityWithVerifiedAttestation(
        ?OneTimeToken $oneTimeToken,
        string $expectedEventClass,
    ): void {
        $challengeKey = new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174000');
        $passkeyUserId = new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $email = new Email('passkey@example.com');
        $challenge = new RegistrationChallenge(
            $challengeKey,
            new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
            new WebAuthnOptions('{"challenge":"challenge"}'),
            new DateTimeImmutable('+5 minutes'),
            $passkeyUserId,
            $email,
            new SignupSession(AccountType::INDIVIDUAL, $oneTimeToken, '/dashboard'),
        );
        $passkeyUser = new PasskeyUser($passkeyUserId, null);
        $identity = new Identity(
            new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174002'),
            new IdentityName('Passkey User'),
            $email,
            Language::ENGLISH,
            null,
            null,
        );
        $verified = new VerifiedPasskeyCredential(
            new WebAuthnCredentialId('Y3JlZGVudGlhbA'),
            new CredentialSource('{"credential":"source"}'),
            0,
            true,
            true,
            ['hybrid', 'internal'],
        );
        $credential = new PasskeyCredential(
            new PasskeyCredentialIdentifier('123e4567-e89b-72d3-a456-426614174003'),
            $passkeyUserId,
            $verified->credentialId,
            $verified->credentialSource,
            0,
            true,
            true,
            $verified->transports,
            new PasskeyDisplayName('Personal passkey'),
            null,
        );

        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('consumeRegistration')->once()->with($challengeKey)->andReturn($challenge);
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->with($passkeyUserId)->andReturn($passkeyUser);
        $passkeyUserRepository->shouldReceive('save')->once()->with($passkeyUser);
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->with($email)->andReturnNull();
        $identityRepository->shouldReceive('save')->once()->with($identity);
        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldReceive('create')->once()->with(
            Mockery::on(static fn (IdentityName $name): bool => (string) $name === 'Passkey User'),
            $email,
            Language::ENGLISH,
        )->andReturn($identity);
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('verifyRegistration')->once()->with(Mockery::on(
            static fn (RegistrationVerificationInput $verification): bool => $verification->responseJson === '{"id":"Y3JlZGVudGlhbA"}'
                && $verification->optionsJson === $challenge->options->json(),
        ))->andReturn($verified);
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByCredentialId')->once()->with($verified->credentialId)->andReturnNull();
        $passkeyCredentialRepository->shouldReceive('save')->once()->with($credential);
        $credentialFactory = Mockery::mock(PasskeyCredentialFactoryInterface::class);
        $credentialFactory->shouldReceive('create')->once()->andReturn($credential);
        $events = Mockery::mock(EventDispatcherInterface::class);
        $events->shouldReceive('dispatch')->once()->with(Mockery::on(
            static fn (object $event): bool => (
                $expectedEventClass === IdentityCreated::class
                && $event instanceof IdentityCreated
                && $event->identityIdentifier === $identity->identityIdentifier()
                && $event->email === $email
                && $event->accountType === AccountType::INDIVIDUAL
            ) || (
                $expectedEventClass === IdentityCreatedViaInvitation::class
                && $event instanceof IdentityCreatedViaInvitation
                && $event->identityIdentifier === $identity->identityIdentifier()
                && $event->oneTimeToken === $oneTimeToken
            ),
        ));
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('login')->once()->with($identity)->andReturn($identity);
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $invitationValidator = Mockery::mock(SignupInvitationValidatorInterface::class);
        if ($oneTimeToken !== null) {
            $invitationValidator->shouldReceive('validate')->once()->with($oneTimeToken, $email);
        } else {
            $invitationValidator->shouldNotReceive('validate');
        }

        foreach ([
            ChallengeSessionStorageServiceInterface::class => $storage,
            SignupInvitationValidatorInterface::class => $invitationValidator,
            PasskeyUserRepositoryInterface::class => $passkeyUserRepository,
            IdentityRepositoryInterface::class => $identityRepository,
            IdentityFactoryInterface::class => $identityFactory,
            WebAuthnServiceInterface::class => $webAuthn,
            PasskeyCredentialRepositoryInterface::class => $passkeyCredentialRepository,
            PasskeyCredentialFactoryInterface::class => $credentialFactory,
            EventDispatcherInterface::class => $events,
            AuthServiceInterface::class => $auth,
            ImageServiceInterface::class => $imageService,
        ] as $interface => $implementation) {
            $this->app->instance($interface, $implementation);
        }

        $output = new RegisterWithPasskeyOutput();
        $this->app->make(RegisterWithPasskeyInterface::class)->process(new RegisterWithPasskeyInput(
            $challengeKey,
            new IdentityName('Passkey User'),
            Language::ENGLISH,
            new PasskeyDisplayName('Personal passkey'),
            '{"id":"Y3JlZGVudGlhbA"}',
            null,
        ), $output);

        $this->assertSame('123e4567-e89b-72d3-a456-426614174002', $output->toArray()['identityIdentifier']);
        $this->assertSame('/dashboard', $output->toArray()['returnTo']);
        $this->assertSame($identity->identityIdentifier(), $passkeyUser->identityIdentifier());
    }

    /** @return array<string, array{?OneTimeToken, class-string}> */
    public static function signupSessionProvider(): array
    {
        return [
            'regular signup' => [null, IdentityCreated::class],
            'invited signup' => [new OneTimeToken(str_repeat('a', 64)), IdentityCreatedViaInvitation::class],
        ];
    }

    public function testItRejectsInvalidOrExpiredChallenge(): void
    {
        /** @var ChallengeSessionStorageServiceInterface&Mockery\MockInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('consumeRegistration')->once()->andThrow(new ChallengeSessionNotFoundException());
        $this->bindFailureDependencies(storage: $storage);

        $this->expectException(ChallengeSessionNotFoundException::class);
        $this->app->make(RegisterWithPasskeyInterface::class)->process($this->input(), new RegisterWithPasskeyOutput());
    }

    public function testItRejectsMissingPasskeyUser(): void
    {
        /** @var PasskeyUserRepositoryInterface&Mockery\MockInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->andReturnNull();
        $this->bindFailureDependencies(passkeyUserRepository: $passkeyUserRepository);

        $this->expectException(PasskeyUserNotFoundException::class);
        $this->app->make(RegisterWithPasskeyInterface::class)->process($this->input(), new RegisterWithPasskeyOutput());
    }

    public function testItRejectsAlreadyLinkedPasskeyUser(): void
    {
        /** @var PasskeyUserRepositoryInterface&Mockery\MockInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->andReturn(new PasskeyUser(
            new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174001'),
            new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174099'),
        ));
        $this->bindFailureDependencies(passkeyUserRepository: $passkeyUserRepository);

        $this->expectException(PasskeyUserAlreadyLinkedException::class);
        $this->app->make(RegisterWithPasskeyInterface::class)->process($this->input(), new RegisterWithPasskeyOutput());
    }

    public function testItRejectsDuplicateEmail(): void
    {
        /** @var IdentityRepositoryInterface&Mockery\MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->andReturn(Mockery::mock(Identity::class));
        $this->bindFailureDependencies(identityRepository: $identityRepository);

        $this->expectException(AlreadyUserExistsException::class);
        $this->app->make(RegisterWithPasskeyInterface::class)->process($this->input(), new RegisterWithPasskeyOutput());
    }

    public function testItRejectsInvalidAttestation(): void
    {
        /** @var WebAuthnServiceInterface&Mockery\MockInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('verifyRegistration')->once()->andThrow(new WebAuthnVerificationException());
        $this->bindFailureDependencies(webAuthn: $webAuthn);

        $this->expectException(WebAuthnVerificationException::class);
        $this->app->make(RegisterWithPasskeyInterface::class)->process($this->input(), new RegisterWithPasskeyOutput());
    }

    public function testItRejectsDuplicateCredential(): void
    {
        $verified = new VerifiedPasskeyCredential(
            new WebAuthnCredentialId('Y3JlZGVudGlhbA'),
            new CredentialSource('{"credential":"source"}'),
            0,
            false,
            false,
            ['usb'],
        );
        /** @var WebAuthnServiceInterface&Mockery\MockInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('verifyRegistration')->once()->andReturn($verified);
        /** @var PasskeyCredentialRepositoryInterface&Mockery\MockInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByCredentialId')->once()->andReturn(Mockery::mock(PasskeyCredential::class));
        $this->bindFailureDependencies(passkeyCredentialRepository: $passkeyCredentialRepository, webAuthn: $webAuthn);

        $this->expectException(PasskeyCredentialAlreadyExistsException::class);
        $this->app->make(RegisterWithPasskeyInterface::class)->process($this->input(), new RegisterWithPasskeyOutput());
    }

    public function testItRevalidatesInvitationBeforeCompletingRegistration(): void
    {
        $token = new OneTimeToken(str_repeat('a', 64));
        $email = new Email('passkey@example.com');
        $challenge = new RegistrationChallenge(
            new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174000'),
            new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
            new WebAuthnOptions('{"challenge":"challenge"}'),
            new DateTimeImmutable('+5 minutes'),
            new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174001'),
            $email,
            new SignupSession(AccountType::INDIVIDUAL, $token, '/dashboard'),
        );
        /** @var ChallengeSessionStorageServiceInterface&Mockery\MockInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('consumeRegistration')->once()->andReturn($challenge);
        /** @var SignupInvitationValidatorInterface&Mockery\MockInterface $validator */
        $validator = Mockery::mock(SignupInvitationValidatorInterface::class);
        $validator->shouldReceive('validate')
            ->once()
            ->with($token, $email)
            ->andThrow(new InvalidSignupInvitationException());

        $this->bindFailureDependencies(storage: $storage, invitationValidator: $validator);

        $this->expectException(InvalidSignupInvitationException::class);
        $this->app->make(RegisterWithPasskeyInterface::class)->process($this->input(), new RegisterWithPasskeyOutput());
    }

    private function input(): RegisterWithPasskeyInput
    {
        return new RegisterWithPasskeyInput(
            new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174000'),
            new IdentityName('Passkey User'),
            Language::ENGLISH,
            new PasskeyDisplayName('Personal passkey'),
            '{"id":"Y3JlZGVudGlhbA"}',
            null,
        );
    }

    private function challenge(): RegistrationChallenge
    {
        return new RegistrationChallenge(
            new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174000'),
            new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
            new WebAuthnOptions('{"challenge":"challenge"}'),
            new DateTimeImmutable('+5 minutes'),
            new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174001'),
            new Email('passkey@example.com'),
            new SignupSession(),
        );
    }

    private function bindFailureDependencies(
        ?ChallengeSessionStorageServiceInterface $storage = null,
        ?PasskeyUserRepositoryInterface $passkeyUserRepository = null,
        ?IdentityRepositoryInterface $identityRepository = null,
        ?PasskeyCredentialRepositoryInterface $passkeyCredentialRepository = null,
        ?WebAuthnServiceInterface $webAuthn = null,
        ?SignupInvitationValidatorInterface $invitationValidator = null,
    ): void {
        if ($storage === null) {
            $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
            $storage->shouldReceive('consumeRegistration')->zeroOrMoreTimes()->andReturn($this->challenge());
        }
        if ($passkeyUserRepository === null) {
            $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
            $passkeyUserRepository->shouldReceive('findByIdentifier')->zeroOrMoreTimes()->andReturn(new PasskeyUser(
                new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174001'),
                null,
            ));
        }
        if ($identityRepository === null) {
            $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
            $identityRepository->shouldReceive('findByEmail')->zeroOrMoreTimes()->andReturnNull();
        }
        $passkeyCredentialRepository ??= Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $webAuthn ??= Mockery::mock(WebAuthnServiceInterface::class);
        $invitationValidator ??= Mockery::mock(SignupInvitationValidatorInterface::class);

        foreach ([
            ChallengeSessionStorageServiceInterface::class => $storage,
            SignupInvitationValidatorInterface::class => $invitationValidator,
            PasskeyUserRepositoryInterface::class => $passkeyUserRepository,
            IdentityRepositoryInterface::class => $identityRepository,
            PasskeyCredentialRepositoryInterface::class => $passkeyCredentialRepository,
            WebAuthnServiceInterface::class => $webAuthn,
            IdentityFactoryInterface::class => Mockery::mock(IdentityFactoryInterface::class),
            PasskeyCredentialFactoryInterface::class => Mockery::mock(PasskeyCredentialFactoryInterface::class),
            EventDispatcherInterface::class => Mockery::mock(EventDispatcherInterface::class),
            AuthServiceInterface::class => Mockery::mock(AuthServiceInterface::class),
            ImageServiceInterface::class => Mockery::mock(ImageServiceInterface::class),
        ] as $interface => $implementation) {
            $this->app->instance($interface, $implementation);
        }
    }
}
