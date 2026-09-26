<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\AddPasskey;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AdditionChallenge;
use Source\Identity\Application\Service\WebAuthn\RegistrationVerificationInput;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyCredential;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskey;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskeyInput;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskeyInterface;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskeyOutput;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Exception\PasskeyCredentialAlreadyExistsException;
use Source\Identity\Domain\Exception\PasskeyUserNotFoundException;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\Factory\PasskeyCredentialFactoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class AddPasskeyTest extends TestCase
{
    private const string CHALLENGE_KEY = '123e4567-e89b-72d3-a456-426614174000';
    private const string IDENTITY_ID = '123e4567-e89b-72d3-a456-426614174001';
    private const string PASSKEY_USER_ID = '123e4567-e89b-72d3-a456-426614174002';
    private const string CREDENTIAL_ID = 'Y3JlZGVudGlhbA';

    public function testItIsBound(): void
    {
        $this->bindDependencies();

        $this->assertInstanceOf(AddPasskey::class, $this->app->make(AddPasskeyInterface::class));
    }

    /** @param string[] $transports */
    #[DataProvider('credentialVariants')]
    public function testItConsumesIdentityBoundChallengeAndSavesCredential(
        bool $backupEligible,
        bool $backupState,
        array $transports,
    ): void {
        $input = $this->input();
        $challenge = $this->challenge();
        $passkeyUser = new PasskeyUser(
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            new IdentityIdentifier(self::IDENTITY_ID),
        );
        $verified = new VerifiedPasskeyCredential(
            new WebAuthnCredentialId(self::CREDENTIAL_ID),
            new CredentialSource('{"credential":"source"}'),
            0,
            $backupEligible,
            $backupState,
            $transports,
        );
        $credential = new PasskeyCredential(
            new PasskeyCredentialIdentifier('123e4567-e89b-72d3-a456-426614174003'),
            $passkeyUser->identifier(),
            $verified->credentialId,
            $verified->credentialSource,
            $verified->signCount,
            $verified->backupEligible,
            $verified->backupState,
            $verified->transports,
            new PasskeyDisplayName('Work key'),
            null,
        );

        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('consumeAddition')->once()->with(
            Mockery::on(static fn (ChallengeSessionKey $key): bool => (string) $key === self::CHALLENGE_KEY),
            Mockery::on(static fn (IdentityIdentifier $id): bool => (string) $id === self::IDENTITY_ID),
        )->andReturn($challenge);
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldReceive('findByIdentityIdentifier')->once()->andReturn($passkeyUser);
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('verifyRegistration')->once()->with(Mockery::on(
            static fn (RegistrationVerificationInput $verification): bool =>
                $verification->responseJson === '{"id":"' . self::CREDENTIAL_ID . '"}'
                && $verification->optionsJson === $challenge->options->json(),
        ))->andReturn($verified);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByCredentialId')->once()->with($verified->credentialId)->andReturnNull();
        $credentials->shouldReceive('save')->once()->with($credential);
        /** @var MockInterface&PasskeyCredentialFactoryInterface $factory */
        $factory = Mockery::mock(PasskeyCredentialFactoryInterface::class);
        $factory->shouldReceive('create')->once()->with(
            $passkeyUser->identifier(),
            $verified->credentialId,
            $verified->credentialSource,
            0,
            $backupEligible,
            $backupState,
            $transports,
            Mockery::on(static fn (PasskeyDisplayName $name): bool => (string) $name === 'Work key'),
        )->andReturn($credential);
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldReceive('requireValid')->once()->with(
            Mockery::on(static fn (IdentityIdentifier $id): bool => (string) $id === self::IDENTITY_ID),
            StepUpAuthenticationScope::PASSKEY_MANAGE,
        )->andReturn(new StepUpAuthentication(
            new IdentityIdentifier(self::IDENTITY_ID),
            StepUpAuthenticationMethod::PASSKEY,
            new DateTimeImmutable(),
            StepUpAuthenticationScope::PASSKEY_MANAGE,
            new DateTimeImmutable('+10 minutes'),
        ));
        $this->bindDependencies($storage, $users, $credentials, $factory, $webAuthn, $stepUp);

        $output = new AddPasskeyOutput();
        $this->app->make(AddPasskeyInterface::class)->process($input, $output);

        $this->assertSame([], $output->toArray());
    }

    /** @return array<string, array{bool, bool, string[]}> */
    public static function credentialVariants(): array
    {
        return [
            'synchronized passkey' => [true, true, ['hybrid', 'internal']],
            'device-bound passkey' => [false, false, ['internal']],
            'physical security key' => [false, false, ['usb', 'nfc']],
        ];
    }

    public function testItRejectsDuplicateCredentialId(): void
    {
        $verified = new VerifiedPasskeyCredential(
            new WebAuthnCredentialId(self::CREDENTIAL_ID),
            new CredentialSource('{"credential":"source"}'),
            0,
            false,
            false,
            ['usb'],
        );
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldReceive('findByIdentityIdentifier')->once()->andReturn(new PasskeyUser(
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            new IdentityIdentifier(self::IDENTITY_ID),
        ));
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('verifyRegistration')->once()->andReturn($verified);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByCredentialId')->once()->andReturn(Mockery::mock(PasskeyCredential::class));
        $this->bindDependencies(users: $users, credentials: $credentials, webAuthn: $webAuthn);

        $this->expectException(PasskeyCredentialAlreadyExistsException::class);
        $this->app->make(AddPasskeyInterface::class)->process($this->input(), new AddPasskeyOutput());
    }

    public function testItRejectsIdentityWithoutPasskeyUser(): void
    {
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldReceive('findByIdentityIdentifier')->once()->andReturnNull();
        $this->bindDependencies(users: $users);

        $this->expectException(PasskeyUserNotFoundException::class);
        $this->app->make(AddPasskeyInterface::class)->process($this->input(), new AddPasskeyOutput());
    }

    public function testItRejectsAValidRegistrationWithoutStepUpAuthorization(): void
    {
        $passkeyUser = new PasskeyUser(
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            new IdentityIdentifier(self::IDENTITY_ID),
        );
        $verified = new VerifiedPasskeyCredential(
            new WebAuthnCredentialId(self::CREDENTIAL_ID),
            new CredentialSource('{"credential":"source"}'),
            0,
            false,
            false,
            ['internal'],
        );
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldReceive('findByIdentityIdentifier')->once()->andReturn($passkeyUser);
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('verifyRegistration')->once()->andReturn($verified);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByCredentialId')->once()->andReturnNull();
        $credentials->shouldNotReceive('save');
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldReceive('requireValid')->once()->andThrow(new StepUpAuthenticationRequiredException());
        $this->bindDependencies(users: $users, credentials: $credentials, webAuthn: $webAuthn, stepUp: $stepUp);

        $this->expectException(StepUpAuthenticationRequiredException::class);
        $this->app->make(AddPasskeyInterface::class)->process($this->input(), new AddPasskeyOutput());
    }

    private function input(): AddPasskeyInput
    {
        return new AddPasskeyInput(
            new IdentityIdentifier(self::IDENTITY_ID),
            new ChallengeSessionKey(self::CHALLENGE_KEY),
            new PasskeyDisplayName('Work key'),
            '{"id":"' . self::CREDENTIAL_ID . '"}',
        );
    }

    private function challenge(): AdditionChallenge
    {
        return new AdditionChallenge(
            new ChallengeSessionKey(self::CHALLENGE_KEY),
            new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
            new WebAuthnOptions('{"challenge":"challenge"}'),
            new DateTimeImmutable('+5 minutes'),
            new IdentityIdentifier(self::IDENTITY_ID),
        );
    }

    private function bindDependencies(
        ?ChallengeSessionStorageServiceInterface $storage = null,
        ?PasskeyUserRepositoryInterface $users = null,
        ?PasskeyCredentialRepositoryInterface $credentials = null,
        ?PasskeyCredentialFactoryInterface $factory = null,
        ?WebAuthnServiceInterface $webAuthn = null,
        ?StepUpAuthenticationStorageServiceInterface $stepUp = null,
    ): void {
        $storage ??= Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $users ??= Mockery::mock(PasskeyUserRepositoryInterface::class);
        $credentials ??= Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $factory ??= Mockery::mock(PasskeyCredentialFactoryInterface::class);
        $webAuthn ??= Mockery::mock(WebAuthnServiceInterface::class);
        $stepUp ??= Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        foreach ([$storage, $users, $credentials, $factory, $webAuthn, $stepUp] as $mock) {
            if ($mock instanceof MockInterface) {
                $mock->shouldIgnoreMissing();
            }
        }
        if ($storage instanceof MockInterface) {
            $storage->shouldReceive('consumeAddition')->zeroOrMoreTimes()->andReturn($this->challenge());
        }
        if ($stepUp instanceof MockInterface) {
            $stepUp->shouldReceive('requireValid')->zeroOrMoreTimes()->andReturn(new StepUpAuthentication(
                new IdentityIdentifier(self::IDENTITY_ID),
                StepUpAuthenticationMethod::PASSKEY,
                new DateTimeImmutable(),
                StepUpAuthenticationScope::PASSKEY_MANAGE,
                new DateTimeImmutable('+10 minutes'),
            ));
        }

        $this->app->instance(ChallengeSessionStorageServiceInterface::class, $storage);
        $this->app->instance(PasskeyUserRepositoryInterface::class, $users);
        $this->app->instance(PasskeyCredentialRepositoryInterface::class, $credentials);
        $this->app->instance(PasskeyCredentialFactoryInterface::class, $factory);
        $this->app->instance(WebAuthnServiceInterface::class, $webAuthn);
        $this->app->instance(StepUpAuthenticationStorageServiceInterface::class, $stepUp);
    }
}
