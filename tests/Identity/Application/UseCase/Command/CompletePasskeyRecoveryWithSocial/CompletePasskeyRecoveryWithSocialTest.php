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
    public function testItIssuesRecoveryForTheIdentityFoundBySocialSubject(): void
    {
        [$useCase, $input, $recoveryKey] = $this->scenario();
        $output = new CompletePasskeyRecoveryWithSocialOutput();
        $useCase->process($input, $output);
        $this->assertSame('/settings/passkeys/recovery?recoveryKey=' . rawurlencode((string) $recoveryKey), $output->redirectUrl());
    }

    public function testItRejectsAnUnlinkedSocialAccount(): void
    {
        [$useCase, $input] = $this->scenario('unlinked');
        $this->expectException(PasskeyRecoveryVerificationFailedException::class);
        $useCase->process($input, new CompletePasskeyRecoveryWithSocialOutput());
    }

    public function testItRejectsAnIdentityWithoutPasskeys(): void
    {
        [$useCase, $input] = $this->scenario('passkey');
        $this->expectException(PasskeyRecoveryVerificationFailedException::class);
        $useCase->process($input, new CompletePasskeyRecoveryWithSocialOutput());
    }

    public function testItRejectsADifferentProvider(): void
    {
        [$useCase, $input] = $this->scenario('provider');
        $this->expectException(PasskeyRecoveryVerificationFailedException::class);
        $useCase->process($input, new CompletePasskeyRecoveryWithSocialOutput());
    }

    public function testItRejectsAMissingOAuthSession(): void
    {
        [$useCase, $input] = $this->scenario('session');
        $this->expectException(PasskeyRecoveryVerificationFailedException::class);
        $useCase->process($input, new CompletePasskeyRecoveryWithSocialOutput());
    }

    /**
     * @return array{CompletePasskeyRecoveryWithSocial, CompletePasskeyRecoveryWithSocialInput, PasskeyRecoveryKey}
     */
    private function scenario(string $failure = ''): array
    {
        $identityId = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $state = new OAuthState('passkey-recovery-state', new DateTimeImmutable('+10 minutes'));
        $code = new OAuthCode('code');
        /** @var MockInterface&OAuthStateRepositoryInterface $oauthStateRepository */
        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')->once()->with($state);
        /** @var MockInterface&PasskeyRecoveryOAuthSessionStorageServiceInterface $oauthSessions */
        $oauthSessions = Mockery::mock(PasskeyRecoveryOAuthSessionStorageServiceInterface::class);
        $oauthSessions->shouldReceive('consume')->once()->with($state)->andReturn($failure === 'session' ? null : new PasskeyRecoveryOAuthSession($failure === 'provider' ? SocialProvider::LINE : SocialProvider::GOOGLE, new DateTimeImmutable('+10 minutes')));
        /** @var MockInterface&SocialOAuthServiceInterface $oauth */
        $oauth = Mockery::mock(SocialOAuthServiceInterface::class);
        $validSession = ! in_array($failure, ['provider', 'session'], true);
        if ($validSession) {
            $oauth->shouldReceive('fetchProfile')->once()->with(SocialProvider::GOOGLE, $code)->andReturn(new SocialProfile(SocialProvider::GOOGLE, 'subject', new Email('user@example.com')));
        } else {
            $oauth->shouldNotReceive('fetchProfile');
        }
        $identity = new Identity($identityId, new IdentityName('user'), new Email('user@example.com'), Language::JAPANESE, null, new DateTimeImmutable());
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        if ($validSession) {
            $identityRepository->shouldReceive('findBySocialConnection')->once()->with(SocialProvider::GOOGLE, 'subject')->andReturn($failure === 'unlinked' ? null : $identity);
        } else {
            $identityRepository->shouldNotReceive('findBySocialConnection');
        }
        $identityRepository->shouldNotReceive('save');
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        if ($validSession && $failure !== 'unlinked') {
            $passkeyCredentialRepository->shouldReceive('findByIdentityIdentifier')->once()->with($identityId)->andReturn($failure === 'passkey' ? [] : [Mockery::mock()]);
        } else {
            $passkeyCredentialRepository->shouldNotReceive('findByIdentityIdentifier');
        }
        $recoveryKey = new PasskeyRecoveryKey('123e4567-e89b-72d3-a456-426614174001');
        /** @var MockInterface&PasskeyRecoverySessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(PasskeyRecoverySessionStorageServiceInterface::class);
        if ($failure !== '') {
            $sessions->shouldNotReceive('issue');
        } else {
            $sessions->shouldReceive('issue')->once()->with($identityId, 'sso')->andReturn($recoveryKey);
        }

        return [new CompletePasskeyRecoveryWithSocial($oauthStateRepository, $oauthSessions, $oauth, $identityRepository, $passkeyCredentialRepository, $sessions), new CompletePasskeyRecoveryWithSocialInput(SocialProvider::GOOGLE, $code, $state), $recoveryKey];
    }
}
