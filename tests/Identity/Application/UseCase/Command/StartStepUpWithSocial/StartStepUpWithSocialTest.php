<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\StartStepUpWithSocial;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\StepUpOAuthSessionStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\StartStepUpWithSocial\StartStepUpWithSocial;
use Source\Identity\Application\UseCase\Command\StartStepUpWithSocial\StartStepUpWithSocialInput;
use Source\Identity\Application\UseCase\Command\StartStepUpWithSocial\StartStepUpWithSocialInterface;
use Source\Identity\Application\UseCase\Command\StartStepUpWithSocial\StartStepUpWithSocialOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Exception\StepUpSocialAuthenticationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Service\OAuthStateGeneratorInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\StepUpOAuthSession;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class StartStepUpWithSocialTest extends TestCase
{
    private const string IDENTITY_ID = '123e4567-e89b-72d3-a456-426614174001';

    public function testItIsBound(): void
    {
        $this->bindDependencies($this->identity(), []);

        $this->assertInstanceOf(StartStepUpWithSocial::class, $this->app->make(StartStepUpWithSocialInterface::class));
    }

    public function testItStartsReauthenticationForALinkedProviderWhenNoPasskeyExists(): void
    {
        $generatedState = new OAuthState('generated-state', new DateTimeImmutable('+10 minutes'));
        /** @var MockInterface&OAuthStateRepositoryInterface $states */
        $states = Mockery::mock(OAuthStateRepositoryInterface::class);
        $states->shouldReceive('store')->once()->with(Mockery::on(
            static fn (OAuthState $state): bool => (string) $state === 'step-up-generated-state',
        ));
        /** @var MockInterface&StepUpOAuthSessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(StepUpOAuthSessionStorageServiceInterface::class);
        $sessions->shouldReceive('store')->once()->with(
            Mockery::on(static fn (OAuthState $state): bool => (string) $state === 'step-up-generated-state'),
            Mockery::on(
                static fn (StepUpOAuthSession $session): bool => (string) $session->identityIdentifier === self::IDENTITY_ID
                && $session->provider === SocialProvider::GOOGLE,
            ),
        );
        /** @var MockInterface&SocialOAuthServiceInterface $oauth */
        $oauth = Mockery::mock(SocialOAuthServiceInterface::class);
        $oauth->shouldReceive('buildRedirectUrl')->once()->with(
            SocialProvider::GOOGLE,
            Mockery::on(static fn (OAuthState $state): bool => (string) $state === 'step-up-generated-state'),
        )->andReturn('https://accounts.example.test/authorize');
        $this->bindDependencies($this->identity(), [], $states, $sessions, $oauth, $generatedState);

        $output = new StartStepUpWithSocialOutput();
        $this->app->make(StartStepUpWithSocialInterface::class)->process($this->input(), $output);

        $this->assertSame('https://accounts.example.test/authorize', $output->toArray()['redirectUrl']);
    }

    public function testItRejectsAnUnlinkedProvider(): void
    {
        $this->bindDependencies($this->identity([new SocialConnection(SocialProvider::LINE, 'line-user')]), []);

        $this->expectException(StepUpSocialAuthenticationFailedException::class);
        $this->app->make(StartStepUpWithSocialInterface::class)->process($this->input(), new StartStepUpWithSocialOutput());
    }

    public function testItRejectsSocialStepUpWhenAPasskeyAlreadyExists(): void
    {
        /** @var MockInterface&PasskeyCredential $credential */
        $credential = Mockery::mock(PasskeyCredential::class);
        $this->bindDependencies($this->identity(), [$credential]);

        $this->expectException(StepUpSocialAuthenticationFailedException::class);
        $this->app->make(StartStepUpWithSocialInterface::class)->process($this->input(), new StartStepUpWithSocialOutput());
    }

    /** @param PasskeyCredential[] $credentials */
    private function bindDependencies(
        ?Identity $identity,
        array $credentials,
        ?OAuthStateRepositoryInterface $states = null,
        ?StepUpOAuthSessionStorageServiceInterface $sessions = null,
        ?SocialOAuthServiceInterface $oauth = null,
        ?OAuthState $generatedState = null,
    ): void {
        $identities = Mockery::mock(IdentityRepositoryInterface::class);
        $identities->shouldReceive('findById')->zeroOrMoreTimes()->andReturn($identity);
        $passkeys = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeys->shouldReceive('findByIdentityIdentifier')->zeroOrMoreTimes()->andReturn($credentials);
        $states ??= Mockery::mock(OAuthStateRepositoryInterface::class);
        $sessions ??= Mockery::mock(StepUpOAuthSessionStorageServiceInterface::class);
        $oauth ??= Mockery::mock(SocialOAuthServiceInterface::class);
        foreach ([$states, $sessions, $oauth] as $mock) {
            if ($mock instanceof MockInterface) {
                $mock->shouldIgnoreMissing();
            }
        }
        $generator = Mockery::mock(OAuthStateGeneratorInterface::class);
        $generator->shouldReceive('generate')->zeroOrMoreTimes()->andReturn($generatedState ?? new OAuthState('state', new DateTimeImmutable('+10 minutes')));

        $this->app->instance(IdentityRepositoryInterface::class, $identities);
        $this->app->instance(PasskeyCredentialRepositoryInterface::class, $passkeys);
        $this->app->instance(OAuthStateRepositoryInterface::class, $states);
        $this->app->instance(StepUpOAuthSessionStorageServiceInterface::class, $sessions);
        $this->app->instance(SocialOAuthServiceInterface::class, $oauth);
        $this->app->instance(OAuthStateGeneratorInterface::class, $generator);
    }

    private function input(): StartStepUpWithSocialInput
    {
        return new StartStepUpWithSocialInput(new IdentityIdentifier(self::IDENTITY_ID), SocialProvider::GOOGLE);
    }

    /** @param SocialConnection[]|null $connections */
    private function identity(?array $connections = null): Identity
    {
        return new Identity(new IdentityIdentifier(self::IDENTITY_ID), new IdentityName('test-user'), new Email('test@example.com'), Language::JAPANESE, null, new DateTimeImmutable(), $connections ?? [new SocialConnection(SocialProvider::GOOGLE, 'google-user')]);
    }
}
