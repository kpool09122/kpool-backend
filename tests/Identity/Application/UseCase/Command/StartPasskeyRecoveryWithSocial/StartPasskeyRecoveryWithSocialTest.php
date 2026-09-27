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
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Service\OAuthStateGeneratorInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Tests\TestCase;

class StartPasskeyRecoveryWithSocialTest extends TestCase
{
    public function testItStartsRecoveryWithoutAnIdentityIdentifier(): void
    {
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
            Mockery::on(static fn (PasskeyRecoveryOAuthSession $session): bool => $session->provider === SocialProvider::GOOGLE),
        );
        /** @var MockInterface&SocialOAuthServiceInterface $oauth */
        $oauth = Mockery::mock(SocialOAuthServiceInterface::class);
        $oauth->shouldReceive('buildRedirectUrl')->once()->with(SocialProvider::GOOGLE, Mockery::type(OAuthState::class))->andReturn('https://example.com/oauth');
        $output = new StartPasskeyRecoveryWithSocialOutput();

        (new StartPasskeyRecoveryWithSocial($oauth, $stateGenerator, $oauthStateRepository, $oauthSessions))
            ->process(new StartPasskeyRecoveryWithSocialInput(SocialProvider::GOOGLE), $output);

        $this->assertSame(['redirectUrl' => 'https://example.com/oauth'], $output->toArray());
    }
}
