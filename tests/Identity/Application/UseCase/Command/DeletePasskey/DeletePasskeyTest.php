<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\DeletePasskey;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Exception\CannotDeleteLastAuthenticationMethodException;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\DeletePasskey\DeletePasskey;
use Source\Identity\Application\UseCase\Command\DeletePasskey\DeletePasskeyInput;
use Source\Identity\Application\UseCase\Command\DeletePasskey\DeletePasskeyInterface;
use Source\Identity\Application\UseCase\Command\DeletePasskey\DeletePasskeyOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\PasskeyCredentialNotFoundException;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class DeletePasskeyTest extends TestCase
{
    private const string IDENTITY_ID = '123e4567-e89b-72d3-a456-426614174001';
    private const string OTHER_IDENTITY_ID = '123e4567-e89b-72d3-a456-426614174002';
    private const string PASSKEY_USER_ID = '123e4567-e89b-72d3-a456-426614174003';
    private const string PASSKEY_ID = '123e4567-e89b-72d3-a456-426614174004';
    private const string OTHER_PASSKEY_ID = '123e4567-e89b-72d3-a456-426614174005';

    public function testItIsBound(): void
    {
        $this->bindDependencies();

        $this->assertInstanceOf(DeletePasskey::class, $this->app->make(DeletePasskeyInterface::class));
    }

    public function testItDeletesAnOwnedPasskeyWhenAnotherPasskeyRemains(): void
    {
        $credential = $this->credential(self::PASSKEY_ID);
        $otherCredential = $this->credential(self::OTHER_PASSKEY_ID);
        $passkeyCredentialRepository = $this->credentialsForOwnedPasskey($credential, [$credential, $otherCredential]);
        $passkeyCredentialRepository->shouldReceive('delete')->once()->with(Mockery::on(
            static fn (PasskeyCredentialIdentifier $identifier): bool => (string) $identifier === self::PASSKEY_ID,
        ));
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
        $this->bindDependencies($passkeyCredentialRepository, $this->ownedPasskeyUserRepository(), $this->identityRepository($this->identity()), $stepUp);

        $output = new DeletePasskeyOutput();
        $this->app->make(DeletePasskeyInterface::class)->process($this->input(), $output);

        $this->assertSame([], $output->toArray());
    }

    public function testItDeletesTheLastPasskeyWhenSsoRemains(): void
    {
        $credential = $this->credential(self::PASSKEY_ID);
        $passkeyCredentialRepository = $this->credentialsForOwnedPasskey($credential, [$credential]);
        $passkeyCredentialRepository->shouldReceive('delete')->once()->with(Mockery::type(PasskeyCredentialIdentifier::class));
        $identity = $this->identity([
            new SocialConnection(SocialProvider::GOOGLE, 'provider-user-id'),
        ]);
        $this->bindDependencies($passkeyCredentialRepository, $this->ownedPasskeyUserRepository(), $this->identityRepository($identity));

        $this->app->make(DeletePasskeyInterface::class)->process($this->input(), new DeletePasskeyOutput());

        $this->addToAssertionCount(1);
    }

    public function testItRejectsDeletingTheLastAuthenticationMethod(): void
    {
        $credential = $this->credential(self::PASSKEY_ID);
        $passkeyCredentialRepository = $this->credentialsForOwnedPasskey($credential, [$credential]);
        $passkeyCredentialRepository->shouldNotReceive('delete');
        $this->bindDependencies($passkeyCredentialRepository, $this->ownedPasskeyUserRepository(), $this->identityRepository($this->identity()));

        $this->expectException(CannotDeleteLastAuthenticationMethodException::class);
        $this->app->make(DeletePasskeyInterface::class)->process($this->input(), new DeletePasskeyOutput());
    }

    public function testItDoesNotRevealAMissingCredential(): void
    {
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentifier')->once()->andReturnNull();
        $passkeyCredentialRepository->shouldNotReceive('findByIdentityIdentifier', 'delete');
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldNotReceive('findByIdentifier');
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldNotReceive('findById');
        $this->bindDependencies($passkeyCredentialRepository, $passkeyUserRepository, $identityRepository);

        $this->expectException(PasskeyCredentialNotFoundException::class);
        $this->app->make(DeletePasskeyInterface::class)->process($this->input(), new DeletePasskeyOutput());
    }

    public function testItDoesNotRevealACredentialWithAMissingPasskeyUser(): void
    {
        $credential = $this->credential(self::PASSKEY_ID);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentifier')->once()->andReturn($credential);
        $passkeyCredentialRepository->shouldNotReceive('findByIdentityIdentifier', 'delete');
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->andReturnNull();
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldNotReceive('findById');
        $this->bindDependencies($passkeyCredentialRepository, $passkeyUserRepository, $identityRepository);

        $this->expectException(PasskeyCredentialNotFoundException::class);
        $this->app->make(DeletePasskeyInterface::class)->process($this->input(), new DeletePasskeyOutput());
    }

    public function testItDoesNotRevealAnotherIdentitysCredential(): void
    {
        $credential = $this->credential(self::PASSKEY_ID);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentifier')->once()->andReturn($credential);
        $passkeyCredentialRepository->shouldNotReceive('findByIdentityIdentifier', 'delete');
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->andReturn(new PasskeyUser(
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            new IdentityIdentifier(self::OTHER_IDENTITY_ID),
        ));
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldNotReceive('findById');
        $this->bindDependencies($passkeyCredentialRepository, $passkeyUserRepository, $identityRepository);

        $this->expectException(PasskeyCredentialNotFoundException::class);
        $this->app->make(DeletePasskeyInterface::class)->process($this->input(), new DeletePasskeyOutput());
    }

    public function testItRejectsDeletionWhenTheAuthenticatedIdentityCannotBeResolved(): void
    {
        $credential = $this->credential(self::PASSKEY_ID);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentifier')->once()->andReturn($credential);
        $passkeyCredentialRepository->shouldNotReceive('findByIdentityIdentifier', 'delete');
        $identityRepository = $this->identityRepository(null);
        $this->bindDependencies($passkeyCredentialRepository, $this->ownedPasskeyUserRepository(), $identityRepository);

        $this->expectException(IdentityNotFoundException::class);
        $this->app->make(DeletePasskeyInterface::class)->process($this->input(), new DeletePasskeyOutput());
    }

    public function testItRejectsDeletionWithoutStepUpAuthorization(): void
    {
        $credential = $this->credential(self::PASSKEY_ID);
        $passkeyCredentialRepository = $this->credentialsForOwnedPasskey($credential, [$credential, $this->credential(self::OTHER_PASSKEY_ID)]);
        $passkeyCredentialRepository->shouldNotReceive('delete');
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldReceive('requireValid')->once()->andThrow(new StepUpAuthenticationRequiredException());
        $this->bindDependencies($passkeyCredentialRepository, $this->ownedPasskeyUserRepository(), $this->identityRepository($this->identity()), $stepUp);

        $this->expectException(StepUpAuthenticationRequiredException::class);
        $this->app->make(DeletePasskeyInterface::class)->process($this->input(), new DeletePasskeyOutput());
    }

    /** @param PasskeyCredential[] $identityCredentials */
    private function credentialsForOwnedPasskey(
        PasskeyCredential $credential,
        array $identityCredentials,
    ): MockInterface&PasskeyCredentialRepositoryInterface {
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentifier')->once()->andReturn($credential);
        $passkeyCredentialRepository->shouldReceive('findByIdentityIdentifier')->once()->with(Mockery::on(
            static fn (IdentityIdentifier $identifier): bool => (string) $identifier === self::IDENTITY_ID,
        ))->andReturn($identityCredentials);

        return $passkeyCredentialRepository;
    }

    private function ownedPasskeyUserRepository(): MockInterface&PasskeyUserRepositoryInterface
    {
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->andReturn(new PasskeyUser(
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            new IdentityIdentifier(self::IDENTITY_ID),
        ));

        return $passkeyUserRepository;
    }

    private function identityRepository(?Identity $identity): MockInterface&IdentityRepositoryInterface
    {
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->with(Mockery::on(
            static fn (IdentityIdentifier $identifier): bool => (string) $identifier === self::IDENTITY_ID,
        ))->andReturn($identity);

        return $identityRepository;
    }

    /** @param SocialConnection[] $socialConnections */
    private function identity(array $socialConnections = []): Identity
    {
        return new Identity(
            new IdentityIdentifier(self::IDENTITY_ID),
            new IdentityName('test-user'),
            new Email('test@example.com'),
            Language::JAPANESE,
            null,
            new DateTimeImmutable(),
            $socialConnections,
        );
    }

    private function input(): DeletePasskeyInput
    {
        return new DeletePasskeyInput(
            new IdentityIdentifier(self::IDENTITY_ID),
            new PasskeyCredentialIdentifier(self::PASSKEY_ID),
        );
    }

    private function credential(string $identifier): PasskeyCredential
    {
        return new PasskeyCredential(
            new PasskeyCredentialIdentifier($identifier),
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            new WebAuthnCredentialId('Y3JlZGVudGlhbC-' . substr($identifier, -1)),
            new CredentialSource('{"credential":"source"}'),
            0,
            false,
            false,
            ['internal'],
            new PasskeyDisplayName('Passkey'),
            null,
        );
    }

    private function bindDependencies(
        ?PasskeyCredentialRepositoryInterface $passkeyCredentialRepository = null,
        ?PasskeyUserRepositoryInterface $passkeyUserRepository = null,
        ?IdentityRepositoryInterface $identityRepository = null,
        ?StepUpAuthenticationStorageServiceInterface $stepUp = null,
    ): void {
        $passkeyCredentialRepository ??= Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyUserRepository ??= Mockery::mock(PasskeyUserRepositoryInterface::class);
        $identityRepository ??= Mockery::mock(IdentityRepositoryInterface::class);
        $stepUp ??= Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        foreach ([$passkeyCredentialRepository, $passkeyUserRepository, $identityRepository, $stepUp] as $mock) {
            if ($mock instanceof MockInterface) {
                $mock->shouldIgnoreMissing();
            }
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

        $this->app->instance(PasskeyCredentialRepositoryInterface::class, $passkeyCredentialRepository);
        $this->app->instance(PasskeyUserRepositoryInterface::class, $passkeyUserRepository);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(StepUpAuthenticationStorageServiceInterface::class, $stepUp);
    }
}
