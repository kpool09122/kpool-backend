<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\AddPasskeyOptions;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AdditionChallenge;
use Source\Identity\Application\Service\WebAuthn\RegistrationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptions;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptionsInput;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptionsInterface;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptionsOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidDelegationException;
use Source\Identity\Domain\Factory\PasskeyUserFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class AddPasskeyOptionsTest extends TestCase
{
    private const string IDENTITY_ID = '01994e3a-a15e-72d3-a456-426614174000';
    private const string PASSKEY_USER_ID = '01994e3a-a15e-72d3-a456-426614174001';
    private const string CHALLENGE_KEY = '01994e3a-a15e-72d3-a456-426614174002';
    private const string CHALLENGE = 'MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY';

    public function testItIsBound(): void
    {
        $this->bindDefaults();

        $this->assertInstanceOf(AddPasskeyOptions::class, $this->app->make(AddPasskeyOptionsInterface::class));
    }

    public function testItReusesTheExistingPasskeyUserAndExcludesAllExistingCredentials(): void
    {
        $identity = $this->identity();
        $passkeyUser = new PasskeyUser(new PasskeyUserIdentifier(self::PASSKEY_USER_ID), $identity->identityIdentifier());
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->with($identity->identityIdentifier())->andReturn($identity);
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentityIdentifier')->once()->with($identity->identityIdentifier())->andReturn($passkeyUser);
        $passkeyUserRepository->shouldNotReceive('save');
        /** @var MockInterface&PasskeyUserFactoryInterface $factory */
        $factory = Mockery::mock(PasskeyUserFactoryInterface::class);
        $factory->shouldNotReceive('create');
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByIdentityIdentifier')->once()->with($identity->identityIdentifier())->andReturn([
            $this->credential('credential-one', '01994e3a-a15e-72d3-a456-426614174010'),
            $this->credential('credential-two', '01994e3a-a15e-72d3-a456-426614174011'),
        ]);
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('createRegistrationOptions')->once()->with(Mockery::on(
            static fn (RegistrationOptionsInput $input): bool => $input->userHandle === self::PASSKEY_USER_ID
                && $input->userName === 'passkey@example.com'
                && $input->userDisplayName === 'Passkey User'
                && array_map(static fn (WebAuthnCredentialId $id): string => (string) $id, $input->excludedCredentialIds)
                    === [(string) WebAuthnCredentialId::fromBinary('credential-one'), (string) WebAuthnCredentialId::fromBinary('credential-two')],
        ))->andReturn(new WebAuthnOptions('{"challenge":"challenge"}'));
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('storeAddition')->once()->with(Mockery::on(
            static fn (AdditionChallenge $challenge): bool => (string) $challenge->key === self::CHALLENGE_KEY
                && (string) $challenge->identityIdentifier === self::IDENTITY_ID,
        ));
        $this->bindDefaults($identityRepository, $passkeyUserRepository, $factory, $credentials, $webAuthn, $storage);

        $output = new AddPasskeyOptionsOutput();
        $this->app->make(AddPasskeyOptionsInterface::class)->process(
            new AddPasskeyOptionsInput($identity->identityIdentifier(), null, null),
            $output,
        );

        $this->assertSame([
            'challengeKey' => self::CHALLENGE_KEY,
            'options' => ['challenge' => 'challenge'],
        ], $output->toArray());
    }

    public function testItCreatesLinksAndPersistsAPasskeyUserWhenOneDoesNotExist(): void
    {
        $identity = $this->identity();
        $passkeyUser = new PasskeyUser(
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            $identity->identityIdentifier(),
        );
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->andReturn($identity);
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentityIdentifier')->once()->andReturnNull();
        $passkeyUserRepository->shouldReceive('save')->once()->with(Mockery::on(
            static fn (PasskeyUser $user): bool => $user === $passkeyUser
                && (string) $user->identityIdentifier() === self::IDENTITY_ID,
        ));
        /** @var MockInterface&PasskeyUserFactoryInterface $factory */
        $factory = Mockery::mock(PasskeyUserFactoryInterface::class);
        $factory->shouldReceive('create')->once()->with($identity->identityIdentifier())->andReturn($passkeyUser);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByIdentityIdentifier')->once()->andReturn([]);
        $this->bindDefaults($identityRepository, $passkeyUserRepository, $factory, $credentials);

        $this->app->make(AddPasskeyOptionsInterface::class)->process(
            new AddPasskeyOptionsInput($identity->identityIdentifier(), null, null),
            new AddPasskeyOptionsOutput(),
        );
    }

    public function testItDoesNotPersistANewPasskeyUserWhenOptionsGenerationFails(): void
    {
        $identity = $this->identity();
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->andReturn($identity);
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentityIdentifier')->once()->andReturnNull();
        $passkeyUserRepository->shouldNotReceive('save');
        /** @var MockInterface&PasskeyUserFactoryInterface $factory */
        $factory = Mockery::mock(PasskeyUserFactoryInterface::class);
        $factory->shouldReceive('create')->once()->andReturn(new PasskeyUser(new PasskeyUserIdentifier(self::PASSKEY_USER_ID), $identity->identityIdentifier()));
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByIdentityIdentifier')->once()->andReturn([]);
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('createRegistrationOptions')->once()->andThrow(new RuntimeException('options failed'));
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldNotReceive('storeAddition');
        $this->bindDefaults($identityRepository, $passkeyUserRepository, $factory, $credentials, $webAuthn, $storage);

        $this->expectException(RuntimeException::class);
        $this->app->make(AddPasskeyOptionsInterface::class)->process(
            new AddPasskeyOptionsInput($identity->identityIdentifier(), null, null),
            new AddPasskeyOptionsOutput(),
        );
    }

    public function testItKeepsANewPasskeyUserWhenChallengeStorageFailsSoARetryReusesIt(): void
    {
        $identity = $this->identity();
        $passkeyUser = new PasskeyUser(new PasskeyUserIdentifier(self::PASSKEY_USER_ID), $identity->identityIdentifier());
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->andReturn($identity);
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentityIdentifier')->once()->andReturnNull();
        $passkeyUserRepository->shouldReceive('save')->once()->with($passkeyUser);
        /** @var MockInterface&PasskeyUserFactoryInterface $factory */
        $factory = Mockery::mock(PasskeyUserFactoryInterface::class);
        $factory->shouldReceive('create')->once()->andReturn($passkeyUser);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByIdentityIdentifier')->once()->andReturn([]);
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('storeAddition')->once()->andThrow(new RuntimeException('redis failed'));
        $this->bindDefaults($identityRepository, $passkeyUserRepository, $factory, $credentials, storage: $storage);

        $this->expectException(RuntimeException::class);
        $this->app->make(AddPasskeyOptionsInterface::class)->process(
            new AddPasskeyOptionsInput($identity->identityIdentifier(), null, null),
            new AddPasskeyOptionsOutput(),
        );
    }

    public function testItRejectsADelegatedIdentityBeforeLoadingIdentityData(): void
    {
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldNotReceive('findById');
        $this->bindDefaults(identityRepository: $identityRepository);

        $this->expectException(InvalidDelegationException::class);
        $this->app->make(AddPasskeyOptionsInterface::class)->process(
            new AddPasskeyOptionsInput(
                new IdentityIdentifier(self::IDENTITY_ID),
                new DelegationIdentifier('01994e3a-a15e-72d3-a456-426614174099'),
                new IdentityIdentifier('01994e3a-a15e-72d3-a456-426614174098'),
            ),
            new AddPasskeyOptionsOutput(),
        );
    }

    public function testItRejectsAnUnknownIdentity(): void
    {
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->andReturnNull();
        $this->bindDefaults(identityRepository: $identityRepository);

        $this->expectException(IdentityNotFoundException::class);
        $this->app->make(AddPasskeyOptionsInterface::class)->process(
            new AddPasskeyOptionsInput(new IdentityIdentifier(self::IDENTITY_ID), null, null),
            new AddPasskeyOptionsOutput(),
        );
    }

    private function bindDefaults(
        ?IdentityRepositoryInterface $identityRepository = null,
        ?PasskeyUserRepositoryInterface $passkeyUserRepository = null,
        ?PasskeyUserFactoryInterface $factory = null,
        ?PasskeyCredentialRepositoryInterface $credentials = null,
        ?WebAuthnServiceInterface $webAuthn = null,
        ?ChallengeSessionStorageServiceInterface $storage = null,
    ): void {
        $identityRepository ??= Mockery::mock(IdentityRepositoryInterface::class);
        $passkeyUserRepository ??= Mockery::mock(PasskeyUserRepositoryInterface::class);
        $factory ??= Mockery::mock(PasskeyUserFactoryInterface::class);
        $credentials ??= Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $webAuthn ??= Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('createRegistrationOptions')->zeroOrMoreTimes()->andReturn(new WebAuthnOptions('{"challenge":"challenge"}'));
        $storage ??= Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('storeAddition')->zeroOrMoreTimes();
        $challengeGenerator = Mockery::mock(WebAuthnChallengeGeneratorInterface::class);
        $challengeGenerator->shouldReceive('generate')->zeroOrMoreTimes()->andReturn(new WebAuthnChallenge(self::CHALLENGE));
        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')->zeroOrMoreTimes()->andReturn(self::CHALLENGE_KEY);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(PasskeyUserRepositoryInterface::class, $passkeyUserRepository);
        $this->app->instance(PasskeyUserFactoryInterface::class, $factory);
        $this->app->instance(PasskeyCredentialRepositoryInterface::class, $credentials);
        $this->app->instance(WebAuthnServiceInterface::class, $webAuthn);
        $this->app->instance(ChallengeSessionStorageServiceInterface::class, $storage);
        $this->app->instance(WebAuthnChallengeGeneratorInterface::class, $challengeGenerator);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
    }

    private function identity(): Identity
    {
        return new Identity(
            new IdentityIdentifier(self::IDENTITY_ID),
            new IdentityName('Passkey User'),
            new Email('passkey@example.com'),
            Language::ENGLISH,
            null,
            new HashedPassword(password_hash('Password1!', PASSWORD_DEFAULT)),
            new DateTimeImmutable(),
        );
    }

    private function credential(string $rawCredentialId, string $identifier): PasskeyCredential
    {
        return new PasskeyCredential(
            new PasskeyCredentialIdentifier($identifier),
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            WebAuthnCredentialId::fromBinary($rawCredentialId),
            new CredentialSource('{"credential":"source"}'),
            0,
            false,
            false,
            ['internal'],
            new PasskeyDisplayName('Test key'),
            null,
        );
    }
}
