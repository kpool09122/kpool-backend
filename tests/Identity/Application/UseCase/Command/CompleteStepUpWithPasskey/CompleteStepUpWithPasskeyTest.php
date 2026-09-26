<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AuthenticationVerificationInput;
use Source\Identity\Application\Service\WebAuthn\StepUpAuthenticationChallenge;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyAuthentication;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey\CompleteStepUpWithPasskey;
use Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey\CompleteStepUpWithPasskeyInput;
use Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey\CompleteStepUpWithPasskeyInterface;
use Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey\CompleteStepUpWithPasskeyOutput;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Exception\PasskeyAuthenticationFailedException;
use Source\Identity\Domain\Exception\WebAuthnVerificationException;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class CompleteStepUpWithPasskeyTest extends TestCase
{
    private const string IDENTITY_ID = '123e4567-e89b-72d3-a456-426614174001';
    private const string OTHER_IDENTITY_ID = '123e4567-e89b-72d3-a456-426614174002';
    private const string PASSKEY_USER_ID = '123e4567-e89b-72d3-a456-426614174003';
    private const string CHALLENGE_KEY = '123e4567-e89b-72d3-a456-426614174004';
    private const string CREDENTIAL_ID = 'Y3JlZGVudGlhbA';

    public function testItIsBound(): void
    {
        $this->bindDependencies();

        $this->assertInstanceOf(CompleteStepUpWithPasskey::class, $this->app->make(CompleteStepUpWithPasskeyInterface::class));
    }

    public function testItVerifiesAnOwnedCredentialAndIssuesPasskeyManageAuthorization(): void
    {
        $credential = $this->credential();
        $challenge = $this->challenge();
        $verified = new VerifiedPasskeyAuthentication(new CredentialSource('{"counter":8}'), 8, true, true);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByCredentialId')->once()->andReturn($credential);
        $credentials->shouldReceive('save')->once()->with($credential);
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldReceive('findByIdentifier')->once()->andReturn($this->passkeyUser(self::IDENTITY_ID));
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('verifyAuthentication')->once()->with(Mockery::on(
            static fn (AuthenticationVerificationInput $input): bool => $input->responseJson === '{"id":"'.self::CREDENTIAL_ID.'"}'
                && $input->optionsJson === $challenge->options->json()
                && $input->expectedUserHandle === self::PASSKEY_USER_ID,
        ))->andReturn($verified);
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldReceive('store')->once()->with(Mockery::on(
            static fn (StepUpAuthentication $authorization): bool => (string) $authorization->identityIdentifier === self::IDENTITY_ID
                && $authorization->method === StepUpAuthenticationMethod::PASSKEY
                && $authorization->expiresAt > $authorization->verifiedAt,
        ));
        $this->bindDependencies($credentials, $users, $webAuthn, $stepUp);

        $this->app->make(CompleteStepUpWithPasskeyInterface::class)->process($this->input(), new CompleteStepUpWithPasskeyOutput());

        $this->assertSame(8, $credential->signCount());
        $this->assertTrue($credential->backupState());
    }

    public function testItRejectsAnUnknownCredentialWithoutIssuingAuthorization(): void
    {
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByCredentialId')->once()->andReturnNull();
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldNotReceive('store');
        $this->bindDependencies(credentials: $credentials, stepUp: $stepUp);

        $this->expectException(PasskeyAuthenticationFailedException::class);
        $this->app->make(CompleteStepUpWithPasskeyInterface::class)->process($this->input(), new CompleteStepUpWithPasskeyOutput());
    }

    public function testItRejectsAnotherIdentitysCredentialWithoutVerifyingIt(): void
    {
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByCredentialId')->once()->andReturn($this->credential());
        $credentials->shouldNotReceive('save');
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldReceive('findByIdentifier')->once()->andReturn($this->passkeyUser(self::OTHER_IDENTITY_ID));
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldNotReceive('verifyAuthentication');
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldNotReceive('store');
        $this->bindDependencies($credentials, $users, $webAuthn, $stepUp);

        $this->expectException(PasskeyAuthenticationFailedException::class);
        $this->app->make(CompleteStepUpWithPasskeyInterface::class)->process($this->input(), new CompleteStepUpWithPasskeyOutput());
    }

    public function testFailedAssertionDoesNotIssueAuthorization(): void
    {
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $credentials */
        $credentials = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $credentials->shouldReceive('findByCredentialId')->once()->andReturn($this->credential());
        $credentials->shouldNotReceive('save');
        /** @var MockInterface&PasskeyUserRepositoryInterface $users */
        $users = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $users->shouldReceive('findByIdentifier')->once()->andReturn($this->passkeyUser(self::IDENTITY_ID));
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('verifyAuthentication')->once()->andThrow(new WebAuthnVerificationException());
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldNotReceive('store');
        $this->bindDependencies($credentials, $users, $webAuthn, $stepUp);

        $this->expectException(WebAuthnVerificationException::class);
        $this->app->make(CompleteStepUpWithPasskeyInterface::class)->process($this->input(), new CompleteStepUpWithPasskeyOutput());
    }

    private function bindDependencies(
        ?PasskeyCredentialRepositoryInterface $credentials = null,
        ?PasskeyUserRepositoryInterface $users = null,
        ?WebAuthnServiceInterface $webAuthn = null,
        ?StepUpAuthenticationStorageServiceInterface $stepUp = null,
    ): void {
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('consumeStepUpAuthentication')->zeroOrMoreTimes()->with(
            Mockery::type(ChallengeSessionKey::class),
            Mockery::on(static fn (IdentityIdentifier $id): bool => (string) $id === self::IDENTITY_ID),
        )->andReturn($this->challenge());
        foreach ([$credentials ??= Mockery::mock(PasskeyCredentialRepositoryInterface::class), $users ??= Mockery::mock(PasskeyUserRepositoryInterface::class), $webAuthn ??= Mockery::mock(WebAuthnServiceInterface::class), $stepUp ??= Mockery::mock(StepUpAuthenticationStorageServiceInterface::class)] as $mock) {
            if ($mock instanceof MockInterface) {
                $mock->shouldIgnoreMissing();
            }
        }
        $this->app->instance(ChallengeSessionStorageServiceInterface::class, $storage);
        $this->app->instance(PasskeyCredentialRepositoryInterface::class, $credentials);
        $this->app->instance(PasskeyUserRepositoryInterface::class, $users);
        $this->app->instance(WebAuthnServiceInterface::class, $webAuthn);
        $this->app->instance(StepUpAuthenticationStorageServiceInterface::class, $stepUp);
    }

    private function input(): CompleteStepUpWithPasskeyInput
    {
        return new CompleteStepUpWithPasskeyInput(new IdentityIdentifier(self::IDENTITY_ID), new ChallengeSessionKey(self::CHALLENGE_KEY), new WebAuthnCredentialId(self::CREDENTIAL_ID), '{"id":"'.self::CREDENTIAL_ID.'"}');
    }

    private function challenge(): StepUpAuthenticationChallenge
    {
        return new StepUpAuthenticationChallenge(new ChallengeSessionKey(self::CHALLENGE_KEY), new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'), new WebAuthnOptions('{"challenge":"challenge"}'), new DateTimeImmutable('+5 minutes'), new IdentityIdentifier(self::IDENTITY_ID));
    }

    private function passkeyUser(string $identityId): PasskeyUser
    {
        return new PasskeyUser(new PasskeyUserIdentifier(self::PASSKEY_USER_ID), new IdentityIdentifier($identityId));
    }

    private function credential(): PasskeyCredential
    {
        return new PasskeyCredential(new PasskeyCredentialIdentifier('123e4567-e89b-72d3-a456-426614174005'), new PasskeyUserIdentifier(self::PASSKEY_USER_ID), new WebAuthnCredentialId(self::CREDENTIAL_ID), new CredentialSource('{"counter":7}'), 7, true, false, ['internal'], new PasskeyDisplayName('Passkey'), null);
    }
}
