<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\UpdatePasskey;

use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskey;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyInput;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyInterface;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyOutput;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Exception\PasskeyCredentialNotFoundException;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
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
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByIdentifier')->once()->with(Mockery::on(
            static fn (PasskeyCredentialIdentifier $identifier): bool => (string) $identifier === self::PASSKEY_ID,
        ))->andReturn($credential);
        $credentials->shouldReceive('save')->once()->with($credential);
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldReceive('findByIdentifier')->once()->with($credential->passkeyUserIdentifier())->andReturn($user);
        $this->bindDependencies($credentials, $users);

        $output = new UpdatePasskeyOutput();
        $this->app->make(UpdatePasskeyInterface::class)->process($this->input(), $output);

        $this->assertSame('Renamed passkey', (string) $credential->displayName());
        $this->assertSame([], $output->toArray());
    }

    public function testItDoesNotRevealAMissingCredential(): void
    {
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByIdentifier')->once()->andReturnNull();
        $credentials->shouldNotReceive('save');
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldNotReceive('findByIdentifier');
        $this->bindDependencies($credentials, $users);

        $this->expectException(PasskeyCredentialNotFoundException::class);
        $this->app->make(UpdatePasskeyInterface::class)->process($this->input(), new UpdatePasskeyOutput());
    }

    public function testItDoesNotRevealACredentialWithAMissingPasskeyUser(): void
    {
        $credential = $this->credential();
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByIdentifier')->once()->andReturn($credential);
        $credentials->shouldNotReceive('save');
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldReceive('findByIdentifier')->once()->with($credential->passkeyUserIdentifier())->andReturnNull();
        $this->bindDependencies($credentials, $users);

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
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByIdentifier')->once()->andReturn($credential);
        $credentials->shouldNotReceive('save');
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldReceive('findByIdentifier')->once()->with($credential->passkeyUserIdentifier())->andReturn($user);
        $this->bindDependencies($credentials, $users);

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
        ?PasskeyCredentialRepositoryInterface $credentials = null,
        ?PasskeyUserRepositoryInterface $users = null,
    ): void {
        $credentials ??= Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $users ??= Mockery::mock(PasskeyUserRepositoryInterface::class);
        foreach ([$credentials, $users] as $mock) {
            if ($mock instanceof MockInterface) {
                $mock->shouldIgnoreMissing();
            }
        }

        $this->app->instance(PasskeyCredentialRepositoryInterface::class, $credentials);
        $this->app->instance(PasskeyUserRepositoryInterface::class, $users);
    }
}
