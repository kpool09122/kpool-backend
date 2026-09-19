<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\SignupInvitationValidatorInterface;
use Source\Identity\Application\Service\WebAuthn\RegistrationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptions;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsInput;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsOutput;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\Entity\ChallengeSession;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeExpiredException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\UnauthorizedEmailException;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\ChallengePurpose;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Tests\TestCase;

class CreatePasskeyRegistrationOptionsTest extends TestCase
{
    private const string CHALLENGE_ID = '01994e3a-a15e-72d3-a456-426614174000';
    private const string IDENTITY_ID = '01994e3a-a15e-72d3-a456-426614174001';
    private const string CHALLENGE = 'MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY';

    public function testItIsBound(): void
    {
        $this->bindHappyPathDependencies();

        $this->assertInstanceOf(
            CreatePasskeyRegistrationOptions::class,
            $this->app->make(CreatePasskeyRegistrationOptionsInterface::class),
        );
    }

    public function testItCreatesOptionsForAVerifiedEmailAndStoresRegistrationContext(): void
    {
        $email = new Email('passkey@example.com');
        $signupSession = new SignupSession();
        $session = $this->verifiedSession($email);

        /** @var MockInterface&AuthCodeSessionRepositoryInterface $authSessions */
        $authSessions = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authSessions->shouldReceive('findByEmail')->once()->with($email)->andReturn($session);
        /** @var MockInterface&IdentityRepositoryInterface $identities */
        $identities = Mockery::mock(IdentityRepositoryInterface::class);
        $identities->shouldReceive('findByEmail')->once()->with($email)->andReturnNull();
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('createRegistrationOptions')
            ->once()
            ->with(Mockery::on(static function (RegistrationOptionsInput $input): bool {
                return (string) $input->challenge === self::CHALLENGE
                    && $input->userHandle === self::IDENTITY_ID
                    && $input->userName === 'passkey@example.com'
                    && $input->userDisplayName === 'passkey@example.com'
                    && $input->excludedCredentialIds === [];
            }))
            ->andReturn(new WebAuthnOptions('{"challenge":"challenge","authenticatorSelection":{"residentKey":"required","userVerification":"required"}}'));
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('store')
            ->once()
            ->with(Mockery::on(static function (ChallengeSession $stored): bool {
                return (string) $stored->identifier() === self::CHALLENGE_ID
                    && $stored->purpose() === ChallengePurpose::REGISTRATION
                    && (string) $stored->registrationContext()?->email() === 'passkey@example.com'
                    && (string) $stored->registrationContext()?->identityIdentifier() === self::IDENTITY_ID
                    && $stored->options() !== '';
            }));

        $useCase = $this->useCase($authSessions, $identities, $webAuthn, $storage);
        $output = new CreatePasskeyRegistrationOptionsOutput();
        $useCase->process(new CreatePasskeyRegistrationOptionsInput($email, $signupSession), $output);

        $this->assertSame([
            'challengeIdentifier' => self::CHALLENGE_ID,
            'options' => [
                'challenge' => 'challenge',
                'authenticatorSelection' => [
                    'residentKey' => 'required',
                    'userVerification' => 'required',
                ],
            ],
        ], $output->toArray());
    }

    public function testInvitationIsValidatedInsteadOfRequiringAnAuthCodeSession(): void
    {
        $email = new Email('invited@example.com');
        $token = new OneTimeToken(str_repeat('a', OneTimeToken::TOKEN_LENGTH));
        $signupSession = new SignupSession(oneTimeToken: $token);
        /** @var MockInterface&AuthCodeSessionRepositoryInterface $authSessions */
        $authSessions = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authSessions->shouldNotReceive('findByEmail');
        /** @var MockInterface&SignupInvitationValidatorInterface $invitationValidator */
        $invitationValidator = Mockery::mock(SignupInvitationValidatorInterface::class);
        $invitationValidator->shouldReceive('validate')->once()->with($token, $email);

        $this->bindHappyPathDependencies($authSessions, invitationValidator: $invitationValidator);
        $output = new CreatePasskeyRegistrationOptionsOutput();
        $this->app->make(CreatePasskeyRegistrationOptionsInterface::class)
            ->process(new CreatePasskeyRegistrationOptionsInput($email, $signupSession), $output);

        $this->assertSame(self::CHALLENGE_ID, $output->toArray()['challengeIdentifier']);
    }

    public function testItRejectsAnExistingIdentity(): void
    {
        $email = new Email('existing@example.com');
        $this->bindHappyPathDependencies(identityExists: true, email: $email);

        $this->expectException(AlreadyUserExistsException::class);
        $this->app->make(CreatePasskeyRegistrationOptionsInterface::class)->process(
            new CreatePasskeyRegistrationOptionsInput($email, new SignupSession()),
            new CreatePasskeyRegistrationOptionsOutput(),
        );
    }

    public function testItRejectsAMissingEmailVerificationSession(): void
    {
        $email = new Email('missing@example.com');
        /** @var MockInterface&AuthCodeSessionRepositoryInterface $authSessions */
        $authSessions = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authSessions->shouldReceive('findByEmail')->once()->with($email)->andReturnNull();
        $this->bindHappyPathDependencies($authSessions, email: $email);

        $this->expectException(AuthCodeSessionNotFoundException::class);
        $this->app->make(CreatePasskeyRegistrationOptionsInterface::class)->process(
            new CreatePasskeyRegistrationOptionsInput($email, new SignupSession()),
            new CreatePasskeyRegistrationOptionsOutput(),
        );
    }

    public function testItRejectsAnUnverifiedEmailSession(): void
    {
        $email = new Email('unverified@example.com');
        /** @var MockInterface&AuthCodeSessionRepositoryInterface $authSessions */
        $authSessions = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authSessions->shouldReceive('findByEmail')->once()->andReturn(new AuthCodeSession(
            $email,
            new AuthCode('123456'),
            new DateTimeImmutable(),
        ));
        $this->bindHappyPathDependencies($authSessions, email: $email);

        $this->expectException(UnauthorizedEmailException::class);
        $this->app->make(CreatePasskeyRegistrationOptionsInterface::class)->process(
            new CreatePasskeyRegistrationOptionsInput($email, new SignupSession()),
            new CreatePasskeyRegistrationOptionsOutput(),
        );
    }

    public function testItRejectsAnExpiredVerificationSession(): void
    {
        $email = new Email('expired@example.com');
        $verifiedAt = new DateTimeImmutable('-16 minutes');
        /** @var MockInterface&AuthCodeSessionRepositoryInterface $authSessions */
        $authSessions = Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        $authSessions->shouldReceive('findByEmail')->once()->andReturn(new AuthCodeSession(
            $email,
            new AuthCode('123456'),
            $verifiedAt,
            $verifiedAt,
        ));
        $this->bindHappyPathDependencies($authSessions, email: $email);

        $this->expectException(AuthCodeExpiredException::class);
        $this->app->make(CreatePasskeyRegistrationOptionsInterface::class)->process(
            new CreatePasskeyRegistrationOptionsInput($email, new SignupSession()),
            new CreatePasskeyRegistrationOptionsOutput(),
        );
    }

    private function verifiedSession(Email $email): AuthCodeSession
    {
        $verifiedAt = new DateTimeImmutable();

        return new AuthCodeSession($email, new AuthCode('123456'), $verifiedAt, $verifiedAt);
    }

    private function bindHappyPathDependencies(
        ?AuthCodeSessionRepositoryInterface $authSessions = null,
        bool $identityExists = false,
        ?Email $email = null,
        ?SignupInvitationValidatorInterface $invitationValidator = null,
    ): void {
        $email ??= new Email('passkey@example.com');
        $authSessions ??= Mockery::mock(AuthCodeSessionRepositoryInterface::class);
        if ($authSessions instanceof \Mockery\MockInterface) {
            $authSessions->shouldReceive('findByEmail')->zeroOrMoreTimes()->andReturn($this->verifiedSession($email));
        }
        /** @var MockInterface&IdentityRepositoryInterface $identities */
        $identities = Mockery::mock(IdentityRepositoryInterface::class);
        $identities->shouldReceive('findByEmail')->zeroOrMoreTimes()->andReturn($identityExists ? Mockery::mock(\Source\Identity\Domain\Entity\Identity::class) : null);
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('createRegistrationOptions')->zeroOrMoreTimes()->andReturn(new WebAuthnOptions('{"challenge":"challenge"}'));
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('store')->zeroOrMoreTimes();
        $generator = Mockery::mock(UuidGeneratorInterface::class);
        $generator->shouldReceive('generate')->andReturn(self::CHALLENGE_ID, self::IDENTITY_ID);
        $challengeGenerator = Mockery::mock(WebAuthnChallengeGeneratorInterface::class);
        $challengeGenerator->shouldReceive('generate')->zeroOrMoreTimes()->andReturn(new WebAuthnChallenge(self::CHALLENGE));
        $invitationValidator ??= Mockery::mock(SignupInvitationValidatorInterface::class);
        $invitationValidator->shouldReceive('validate')->zeroOrMoreTimes();

        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authSessions);
        $this->app->instance(IdentityRepositoryInterface::class, $identities);
        $this->app->instance(WebAuthnServiceInterface::class, $webAuthn);
        $this->app->instance(ChallengeSessionStorageServiceInterface::class, $storage);
        $this->app->instance(UuidGeneratorInterface::class, $generator);
        $this->app->instance(WebAuthnChallengeGeneratorInterface::class, $challengeGenerator);
        $this->app->instance(SignupInvitationValidatorInterface::class, $invitationValidator);
    }

    private function useCase(
        AuthCodeSessionRepositoryInterface $authSessions,
        IdentityRepositoryInterface $identities,
        WebAuthnServiceInterface $webAuthn,
        ChallengeSessionStorageServiceInterface $storage,
    ): CreatePasskeyRegistrationOptionsInterface {
        $this->app->instance(AuthCodeSessionRepositoryInterface::class, $authSessions);
        $this->app->instance(IdentityRepositoryInterface::class, $identities);
        $this->app->instance(WebAuthnServiceInterface::class, $webAuthn);
        $this->app->instance(ChallengeSessionStorageServiceInterface::class, $storage);
        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')->twice()->andReturn(self::CHALLENGE_ID, self::IDENTITY_ID);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $challengeGenerator = Mockery::mock(WebAuthnChallengeGeneratorInterface::class);
        $challengeGenerator->shouldReceive('generate')->once()->andReturn(new WebAuthnChallenge(self::CHALLENGE));
        $this->app->instance(WebAuthnChallengeGeneratorInterface::class, $challengeGenerator);
        $validator = Mockery::mock(SignupInvitationValidatorInterface::class);
        $validator->shouldNotReceive('validate');
        $this->app->instance(SignupInvitationValidatorInterface::class, $validator);

        return $this->app->make(CreatePasskeyRegistrationOptionsInterface::class);
    }
}
