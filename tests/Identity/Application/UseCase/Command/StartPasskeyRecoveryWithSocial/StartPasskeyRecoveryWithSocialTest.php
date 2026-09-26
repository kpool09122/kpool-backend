<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSession;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSessionStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial\StartPasskeyRecoveryWithSocial;
use Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial\StartPasskeyRecoveryWithSocialInput;
use Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial\StartPasskeyRecoveryWithSocialOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\PasskeyRecoveryVerificationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Service\OAuthStateGeneratorInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class StartPasskeyRecoveryWithSocialTest extends TestCase
{
    public function testItStartsRecoveryForALinkedProvider(): void
    {
        $identityId = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $identity = $this->identity($identityId, [new SocialConnection(SocialProvider::GOOGLE, 'subject')]);
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->with($identityId)->andReturn($identity);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentityIdentifier')->once()->with($identityId)->andReturn([Mockery::mock()]);
        $generated = new OAuthState('generated', new DateTimeImmutable('+10 minutes'));
        /** @var MockInterface&OAuthStateGeneratorInterface $stateGenerator */
        $stateGenerator = Mockery::mock(OAuthStateGeneratorInterface::class);
        $stateGenerator->shouldReceive('generate')->once()->andReturn($generated);
        /** @var MockInterface&OAuthStateRepositoryInterface $oauthStateRepository */
        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('store')->once()->with(Mockery::on(static fn (OAuthState $state): bool => str_starts_with((string) $state, 'passkey-recovery-')));
        /** @var MockInterface&PasskeyRecoveryOAuthSessionStorageServiceInterface $oauthSessions */
        $oauthSessions = Mockery::mock(PasskeyRecoveryOAuthSessionStorageServiceInterface::class);
        $oauthSessions->shouldReceive('store')->once()->with(
            Mockery::type(OAuthState::class),
            Mockery::on(static fn (PasskeyRecoveryOAuthSession $session): bool => $session->identityIdentifier === $identityId && $session->provider === SocialProvider::GOOGLE),
        );
        /** @var MockInterface&SocialOAuthServiceInterface $oauth */
        $oauth = Mockery::mock(SocialOAuthServiceInterface::class);
        $oauth->shouldReceive('buildRedirectUrl')->once()->with(SocialProvider::GOOGLE, Mockery::type(OAuthState::class))->andReturn('https://example.com/oauth');
        $output = new StartPasskeyRecoveryWithSocialOutput();

        (new StartPasskeyRecoveryWithSocial($identityRepository, $passkeyCredentialRepository, $oauth, $stateGenerator, $oauthStateRepository, $oauthSessions))
            ->process(new StartPasskeyRecoveryWithSocialInput($identityId, SocialProvider::GOOGLE), $output);

        $this->assertSame(['redirectUrl' => 'https://example.com/oauth'], $output->toArray());
    }

    public function testItRejectsAnUnlinkedProvider(): void
    {
        $identityId = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->andReturn($this->identity($identityId, []));
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        /** @var MockInterface&SocialOAuthServiceInterface $oauth */
        $oauth = Mockery::mock(SocialOAuthServiceInterface::class);
        /** @var MockInterface&OAuthStateGeneratorInterface $stateGenerator */
        $stateGenerator = Mockery::mock(OAuthStateGeneratorInterface::class);
        /** @var MockInterface&OAuthStateRepositoryInterface $oauthStateRepository */
        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        /** @var MockInterface&PasskeyRecoveryOAuthSessionStorageServiceInterface $oauthSessions */
        $oauthSessions = Mockery::mock(PasskeyRecoveryOAuthSessionStorageServiceInterface::class);

        $this->expectException(PasskeyRecoveryVerificationFailedException::class);
        (new StartPasskeyRecoveryWithSocial(
            $identityRepository,
            $passkeyCredentialRepository,
            $oauth,
            $stateGenerator,
            $oauthStateRepository,
            $oauthSessions,
        ))->process(new StartPasskeyRecoveryWithSocialInput($identityId, SocialProvider::GOOGLE), new StartPasskeyRecoveryWithSocialOutput());
    }

    /** @param list<SocialConnection> $connections */
    private function identity(IdentityIdentifier $id, array $connections): Identity
    {
        return new Identity($id, new IdentityName('user'), new Email('user@example.com'), Language::JAPANESE, null, new DateTimeImmutable(), $connections);
    }
}
