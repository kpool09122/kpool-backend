<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\UpdatePasskey;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskey;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyInput;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyInterface;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyOutput;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Exception\PasskeyCredentialNotFoundException;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class UpdatePasskeyTest extends TestCase
{
    private const string IDENTITY_ID = '123e4567-e89b-72d3-a456-426614174001';
    private const string OTHER_IDENTITY_ID = '123e4567-e89b-72d3-a456-426614174002';
    private const string PASSKEY_USER_ID = '123e4567-e89b-72d3-a456-426614174003';
    private const string PASSKEY_ID = '123e4567-e89b-72d3-a456-426614174004';

    public function testItIsBound(): void
    {
        $this->bindDependencies();

        $this->assertInstanceOf(UpdatePasskey::class, $this->app->make(UpdatePasskeyInterface::class));
    }

    public function testItRenamesAnOwnedPasskey(): void
    {
        $credential = $this->credential();
        $user = new PasskeyUser(
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            new IdentityIdentifier(self::IDENTITY_ID),
        );
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentifier')->once()->with(Mockery::on(
            static fn (PasskeyCredentialIdentifier $identifier): bool => (string) $identifier === self::PASSKEY_ID,
        ))->andReturn($credential);
        $passkeyCredentialRepository->shouldReceive('save')->once()->with($credential);
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->with($credential->passkeyUserIdentifier())->andReturn($user);
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldReceive('requireValid')->once()->with(
            Mockery::on(static fn (IdentityIdentifier $identifier): bool => (string) $identifier === self::IDENTITY_ID),
            StepUpAuthenticationScope::PASSKEY_MANAGE,
        )->andReturn($this->stepUpAuthentication());
        $this->bindDependencies($passkeyCredentialRepository, $passkeyUserRepository, $stepUp);

        $output = new UpdatePasskeyOutput();
        $this->app->make(UpdatePasskeyInterface::class)->process($this->input(), $output);

        $this->assertSame('Renamed passkey', (string) $credential->displayName());
        $this->assertSame([], $output->toArray());
    }

    public function testItDoesNotRevealAMissingCredential(): void
    {
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentifier')->once()->andReturnNull();
        $passkeyCredentialRepository->shouldNotReceive('save');
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldNotReceive('findByIdentifier');
        $this->bindDependencies($passkeyCredentialRepository, $passkeyUserRepository);

        $this->expectException(PasskeyCredentialNotFoundException::class);
        $this->app->make(UpdatePasskeyInterface::class)->process($this->input(), new UpdatePasskeyOutput());
    }

    public function testItRejectsUpdateWithoutStepUpAuthorizationBeforeLoadingTheCredential(): void
    {
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldNotReceive('findByIdentifier', 'save');
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldNotReceive('findByIdentifier');
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldReceive('requireValid')->once()->andThrow(new StepUpAuthenticationRequiredException());
        $this->bindDependencies($passkeyCredentialRepository, $passkeyUserRepository, $stepUp);

        $this->expectException(StepUpAuthenticationRequiredException::class);
        $this->app->make(UpdatePasskeyInterface::class)->process($this->input(), new UpdatePasskeyOutput());
    }

    public function testItDoesNotRevealACredentialWithAMissingPasskeyUser(): void
    {
        $credential = $this->credential();
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentifier')->once()->andReturn($credential);
        $passkeyCredentialRepository->shouldNotReceive('save');
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->with($credential->passkeyUserIdentifier())->andReturnNull();
        $this->bindDependencies($passkeyCredentialRepository, $passkeyUserRepository);

        $this->expectException(PasskeyCredentialNotFoundException::class);
        $this->app->make(UpdatePasskeyInterface::class)->process($this->input(), new UpdatePasskeyOutput());
    }

    public function testItDoesNotRevealAnotherIdentitysCredential(): void
    {
        $credential = $this->credential();
        $user = new PasskeyUser(
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            new IdentityIdentifier(self::OTHER_IDENTITY_ID),
        );
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentifier')->once()->andReturn($credential);
        $passkeyCredentialRepository->shouldNotReceive('save');
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->with($credential->passkeyUserIdentifier())->andReturn($user);
        $this->bindDependencies($passkeyCredentialRepository, $passkeyUserRepository);

        $this->expectException(PasskeyCredentialNotFoundException::class);
        $this->app->make(UpdatePasskeyInterface::class)->process($this->input(), new UpdatePasskeyOutput());
    }

    private function input(): UpdatePasskeyInput
    {
        return new UpdatePasskeyInput(
            new IdentityIdentifier(self::IDENTITY_ID),
            new PasskeyCredentialIdentifier(self::PASSKEY_ID),
            new PasskeyDisplayName('Renamed passkey'),
        );
    }

    private function credential(): PasskeyCredential
    {
        return new PasskeyCredential(
            new PasskeyCredentialIdentifier(self::PASSKEY_ID),
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            new WebAuthnCredentialId('Y3JlZGVudGlhbA'),
            new CredentialSource('{"credential":"source"}'),
            0,
            false,
            false,
            ['internal'],
            new PasskeyDisplayName('Old passkey'),
            null,
        );
    }

    private function bindDependencies(
        ?PasskeyCredentialRepositoryInterface $passkeyCredentialRepository = null,
        ?PasskeyUserRepositoryInterface $passkeyUserRepository = null,
        ?StepUpAuthenticationStorageServiceInterface $stepUp = null,
    ): void {
        $passkeyCredentialRepository ??= Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyUserRepository ??= Mockery::mock(PasskeyUserRepositoryInterface::class);
        $stepUp ??= Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        foreach ([$passkeyCredentialRepository, $passkeyUserRepository, $stepUp] as $mock) {
            if ($mock instanceof MockInterface) {
                $mock->shouldIgnoreMissing();
            }
        }
        if ($stepUp instanceof MockInterface) {
            $stepUp->shouldReceive('requireValid')->zeroOrMoreTimes()->andReturn($this->stepUpAuthentication());
        }

        $this->app->instance(PasskeyCredentialRepositoryInterface::class, $passkeyCredentialRepository);
        $this->app->instance(PasskeyUserRepositoryInterface::class, $passkeyUserRepository);
        $this->app->instance(StepUpAuthenticationStorageServiceInterface::class, $stepUp);
    }

    private function stepUpAuthentication(): StepUpAuthentication
    {
        return new StepUpAuthentication(
            new IdentityIdentifier(self::IDENTITY_ID),
            StepUpAuthenticationMethod::PASSKEY,
            new DateTimeImmutable(),
            StepUpAuthenticationScope::PASSKEY_MANAGE,
            new DateTimeImmutable('+10 minutes'),
        );
    }
}
