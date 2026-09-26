<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSession;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSessionStorageServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySessionStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial\CompletePasskeyRecoveryWithSocial;
use Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial\CompletePasskeyRecoveryWithSocialInput;
use Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial\CompletePasskeyRecoveryWithSocialOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\PasskeyRecoveryVerificationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class CompletePasskeyRecoveryWithSocialTest extends TestCase
{
    public function testItIssuesRecoveryForTheSameSocialSubject(): void
    {
        [$useCase, $input, $recoveryKey] = $this->scenario(false);
        $output = new CompletePasskeyRecoveryWithSocialOutput();
        $useCase->process($input, $output);
        $this->assertSame('/settings/passkeys/recovery?recoveryKey=' . rawurlencode((string) $recoveryKey), $output->redirectUrl());
    }

    public function testItRejectsADifferentSocialSubject(): void
    {
        [$useCase, $input] = $this->scenario(true);
        $this->expectException(PasskeyRecoveryVerificationFailedException::class);
        $useCase->process($input, new CompletePasskeyRecoveryWithSocialOutput());
    }

    /**
     * @return array{CompletePasskeyRecoveryWithSocial, CompletePasskeyRecoveryWithSocialInput, PasskeyRecoveryKey}
     */
    private function scenario(bool $differentIdentity): array
    {
        $identityId = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $foundId = new IdentityIdentifier($differentIdentity ? '123e4567-e89b-72d3-a456-426614174099' : (string) $identityId);
        $state = new OAuthState('passkey-recovery-state', new DateTimeImmutable('+10 minutes'));
        $code = new OAuthCode('code');
        /** @var MockInterface&OAuthStateRepositoryInterface $oauthStateRepository */
        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')->once()->with($state);
        /** @var MockInterface&PasskeyRecoveryOAuthSessionStorageServiceInterface $oauthSessions */
        $oauthSessions = Mockery::mock(PasskeyRecoveryOAuthSessionStorageServiceInterface::class);
        $oauthSessions->shouldReceive('consume')->once()->with($state)->andReturn(new PasskeyRecoveryOAuthSession($identityId, SocialProvider::GOOGLE, new DateTimeImmutable('+10 minutes')));
        /** @var MockInterface&SocialOAuthServiceInterface $oauth */
        $oauth = Mockery::mock(SocialOAuthServiceInterface::class);
        $oauth->shouldReceive('fetchProfile')->once()->with(SocialProvider::GOOGLE, $code)->andReturn(new SocialProfile(SocialProvider::GOOGLE, 'subject', new Email('user@example.com')));
        $identity = new Identity($foundId, new IdentityName('user'), new Email('user@example.com'), Language::JAPANESE, null, new DateTimeImmutable());
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findBySocialConnection')->once()->andReturn($identity);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentityIdentifier')->zeroOrMoreTimes()->andReturn([Mockery::mock()]);
        $recoveryKey = new PasskeyRecoveryKey('123e4567-e89b-72d3-a456-426614174001');
        /** @var MockInterface&PasskeyRecoverySessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(PasskeyRecoverySessionStorageServiceInterface::class);
        if ($differentIdentity) {
            $sessions->shouldNotReceive('issue');
        } else {
            $sessions->shouldReceive('issue')->once()->with($identityId, 'sso')->andReturn($recoveryKey);
        }

        return [new CompletePasskeyRecoveryWithSocial($oauthStateRepository, $oauthSessions, $oauth, $identityRepository, $passkeyCredentialRepository, $sessions), new CompletePasskeyRecoveryWithSocialInput(SocialProvider::GOOGLE, $code, $state), $recoveryKey];
    }
}
