<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\AuthenticateWithPasskey;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AuthenticationChallenge;
use Source\Identity\Application\Service\WebAuthn\AuthenticationVerificationInput;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyAuthentication;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskey;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskeyInput;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskeyInterface;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskeyOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Exception\PasskeyAuthenticationFailedException;
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
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class AuthenticateWithPasskeyTest extends TestCase
{
    private const string CHALLENGE_KEY = '123e4567-e89b-72d3-a456-426614174000';
    private const string CREDENTIAL_ID = 'Y3JlZGVudGlhbA';
    private const string PASSKEY_USER_ID = '123e4567-e89b-72d3-a456-426614174001';
    private const string IDENTITY_ID = '123e4567-e89b-72d3-a456-426614174002';

    public function testItIsBound(): void
    {
        $this->bindDependencies();

        $this->assertInstanceOf(
            AuthenticateWithPasskey::class,
            $this->app->make(AuthenticateWithPasskeyInterface::class),
        );
    }

    public function testItVerifiesAssertionUpdatesCredentialAndLogsInLinkedIdentity(): void
    {
        $credential = $this->credential();
        $passkeyUser = new PasskeyUser(new PasskeyUserIdentifier(self::PASSKEY_USER_ID), new IdentityIdentifier(self::IDENTITY_ID));
        $identity = $this->identity();
        $challenge = $this->challenge();
        $verified = new VerifiedPasskeyAuthentication(new CredentialSource('{"counter":8}'), 8, true, true);

        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentialRepository */
        $credentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentialRepository->shouldReceive('findByCredentialId')->once()->with(Mockery::on(
            static fn (WebAuthnCredentialId $id): bool => (string) $id === self::CREDENTIAL_ID,
        ))->andReturn($credential);
        $credentialRepository->shouldReceive('save')->once()->with($credential);
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->andReturn($passkeyUser);
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->andReturn($identity);
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('consumeAuthentication')->once()->andReturn($challenge);
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('verifyAuthentication')
            ->once()
            ->with(Mockery::on(static fn (AuthenticationVerificationInput $input): bool =>
                $input->responseJson === '{"id":"' . self::CREDENTIAL_ID . '"}'
                && $input->optionsJson === $challenge->options->json()
                && (string) $input->credentialSource === '{"counter":7}'
                && $input->expectedUserHandle === self::PASSKEY_USER_ID))
            ->andReturn($verified);
        /** @var MockInterface&AuthServiceInterface $auth */
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('login')->once()->with($identity)->andReturn($identity);
        $this->bindDependencies($credentialRepository, $passkeyUserRepository, $identityRepository, $storage, $webAuthn, $auth);

        $output = new AuthenticateWithPasskeyOutput();
        $this->app->make(AuthenticateWithPasskeyInterface::class)->process($this->input(), $output);

        $this->assertSame(8, $credential->signCount());
        $this->assertTrue($credential->backupState());
        $this->assertNotNull($credential->lastUsedAt());
        $this->assertSame(self::IDENTITY_ID, $output->toArray()['identityIdentifier']);
    }

    public function testItRejectsUnknownCredential(): void
    {
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentialRepository */
        $credentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentialRepository->shouldReceive('findByCredentialId')->once()->andReturnNull();
        $this->bindDependencies(credentialRepository: $credentialRepository);

        $this->expectException(PasskeyAuthenticationFailedException::class);
        $this->app->make(AuthenticateWithPasskeyInterface::class)->process($this->input(), new AuthenticateWithPasskeyOutput());
    }

    public function testItRejectsPasskeyUserWithoutLinkedIdentity(): void
    {
        $credential = $this->credential();
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentialRepository */
        $credentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentialRepository->shouldReceive('findByCredentialId')->once()->andReturn($credential);
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentifier')->once()->andReturn(
            new PasskeyUser(new PasskeyUserIdentifier(self::PASSKEY_USER_ID), null),
        );
        $this->bindDependencies($credentialRepository, $passkeyUserRepository);

        $this->expectException(PasskeyAuthenticationFailedException::class);
        $this->app->make(AuthenticateWithPasskeyInterface::class)->process($this->input(), new AuthenticateWithPasskeyOutput());
    }

    private function input(): AuthenticateWithPasskeyInput
    {
        return new AuthenticateWithPasskeyInput(
            new ChallengeSessionKey(self::CHALLENGE_KEY),
            new WebAuthnCredentialId(self::CREDENTIAL_ID),
            '{"id":"' . self::CREDENTIAL_ID . '"}',
        );
    }

    private function challenge(): AuthenticationChallenge
    {
        return new AuthenticationChallenge(
            new ChallengeSessionKey(self::CHALLENGE_KEY),
            new \Source\Identity\Domain\ValueObject\WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'),
            new WebAuthnOptions('{"challenge":"challenge","rpId":"example.com","userVerification":"required"}'),
            new DateTimeImmutable('+5 minutes'),
        );
    }

    private function credential(): PasskeyCredential
    {
        return new PasskeyCredential(
            new PasskeyCredentialIdentifier('123e4567-e89b-72d3-a456-426614174003'),
            new PasskeyUserIdentifier(self::PASSKEY_USER_ID),
            new WebAuthnCredentialId(self::CREDENTIAL_ID),
            new CredentialSource('{"counter":7}'),
            7,
            true,
            false,
            ['internal'],
            new PasskeyDisplayName('My passkey'),
            null,
        );
    }

    private function identity(): Identity
    {
        return new Identity(
            new IdentityIdentifier(self::IDENTITY_ID),
            new IdentityName('test-user'),
            new Email('test@example.com'),
            Language::JAPANESE,
            null,
            new DateTimeImmutable(),
        );
    }

    private function bindDependencies(
        ?PasskeyCredentialRepositoryInterface $credentialRepository = null,
        ?PasskeyUserRepositoryInterface $passkeyUserRepository = null,
        ?IdentityRepositoryInterface $identityRepository = null,
        ?ChallengeSessionStorageServiceInterface $storage = null,
        ?WebAuthnServiceInterface $webAuthn = null,
        ?AuthServiceInterface $auth = null,
    ): void {
        $credentialRepository ??= Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyUserRepository ??= Mockery::mock(PasskeyUserRepositoryInterface::class);
        $identityRepository ??= Mockery::mock(IdentityRepositoryInterface::class);
        $storage ??= Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        if ($storage instanceof MockInterface) {
            $storage->shouldReceive('consumeAuthentication')->zeroOrMoreTimes()->andReturn($this->challenge());
        }
        $webAuthn ??= Mockery::mock(WebAuthnServiceInterface::class);
        $auth ??= Mockery::mock(AuthServiceInterface::class);

        foreach ([$credentialRepository, $passkeyUserRepository, $identityRepository, $storage, $webAuthn, $auth] as $mock) {
            if ($mock instanceof MockInterface) {
                $mock->shouldIgnoreMissing();
            }
        }

        $this->app->instance(PasskeyCredentialRepositoryInterface::class, $credentialRepository);
        $this->app->instance(PasskeyUserRepositoryInterface::class, $passkeyUserRepository);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(ChallengeSessionStorageServiceInterface::class, $storage);
        $this->app->instance(WebAuthnServiceInterface::class, $webAuthn);
        $this->app->instance(AuthServiceInterface::class, $auth);
    }
}
