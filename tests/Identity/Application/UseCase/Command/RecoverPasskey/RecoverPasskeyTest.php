<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\RecoverPasskey;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryNotificationServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySession;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySessionStorageServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\SecurityEventRecorderInterface;
use Source\Identity\Application\Service\WebAuthn\RecoveryRegistrationChallenge;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyCredential;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\RecoverPasskey\RecoverPasskey;
use Source\Identity\Application\UseCase\Command\RecoverPasskey\RecoverPasskeyInput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Exception\WebAuthnVerificationException;
use Source\Identity\Domain\Factory\PasskeyCredentialFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

/**
 * @phpstan-type RecoveryContext array{
 *     identityId: IdentityIdentifier,
 *     recoveryKey: PasskeyRecoveryKey,
 *     identity: Identity,
 *     user: PasskeyUser,
 *     input: RecoverPasskeyInput,
 *     sessions: PasskeyRecoverySessionStorageServiceInterface&MockInterface,
 *     challenges: ChallengeSessionStorageServiceInterface&MockInterface,
 *     identityRepository: IdentityRepositoryInterface&MockInterface,
 *     passkeyUserRepository: PasskeyUserRepositoryInterface&MockInterface,
 *     passkeyCredentialRepository: PasskeyCredentialRepositoryInterface&MockInterface,
 *     factory: PasskeyCredentialFactoryInterface&MockInterface,
 *     webAuthn: WebAuthnServiceInterface&MockInterface,
 *     auth: AuthServiceInterface&MockInterface,
 *     notification: PasskeyRecoveryNotificationServiceInterface&MockInterface,
 *     events: SecurityEventRecorderInterface&MockInterface
 * }
 */
class RecoverPasskeyTest extends TestCase
{
    public function testVerificationFailurePreservesCredentialsAndRecoverySession(): void
    {
        $context = $this->context();
        $context['webAuthn']->shouldReceive('verifyRegistration')->once()->andThrow(new WebAuthnVerificationException());
        $context['passkeyCredentialRepository']->shouldNotReceive('save');
        $context['passkeyCredentialRepository']->shouldNotReceive('deleteAllExcept');
        $context['sessions']->shouldNotReceive('consume');
        $context['auth']->shouldNotReceive('invalidateAllSessions');
        $context['notification']->shouldNotReceive('notifyCompleted');
        $context['events']->shouldNotReceive('record');

        $this->expectException(WebAuthnVerificationException::class);
        $this->useCase($context)->process($context['input']);
    }

    public function testSuccessSavesBeforeDeletingOthersAndCompletesEverySecuritySideEffect(): void
    {
        $context = $this->context('sso');
        $verified = new VerifiedPasskeyCredential(new WebAuthnCredentialId('Y3JlZGVudGlhbA'), new CredentialSource('{}'), 1, true, false, ['internal']);
        $newCredential = new PasskeyCredential(
            new PasskeyCredentialIdentifier('01994e3a-a15e-72d3-a456-426614174010'),
            $context['user']->identifier(),
            $verified->credentialId,
            $verified->credentialSource,
            1,
            true,
            false,
            ['internal'],
            new PasskeyDisplayName('recovered'),
            null,
        );
        $context['webAuthn']->shouldReceive('verifyRegistration')->once()->andReturn($verified);
        $context['passkeyCredentialRepository']->shouldReceive('findByCredentialId')->once()->with($verified->credentialId)->andReturnNull();
        $context['factory']->shouldReceive('create')->once()->andReturn($newCredential);
        $context['passkeyCredentialRepository']->shouldReceive('save')->once()->with($newCredential)->ordered();
        $context['passkeyCredentialRepository']->shouldReceive('deleteAllExcept')->once()->with($context['identityId'], $newCredential->identifier())->ordered();
        $context['sessions']->shouldReceive('consume')->once()->with($context['recoveryKey'], $context['identityId']);
        $context['auth']->shouldReceive('invalidateAllSessions')->once()->with($context['identityId']);
        $context['notification']->shouldReceive('notifyCompleted')->once()->with($context['identity']->email(), $context['identity']->language());
        $context['events']->shouldReceive('record')->once()->with('passkey.recovery.completed', $context['identityId'], ['method' => 'sso']);
        $context['identityRepository']->shouldNotReceive('save');

        $this->useCase($context)->process($context['input']);
        $this->addToAssertionCount(1);
    }

    /** @return RecoveryContext */
    private function context(string $method = 'email'): array
    {
        $identityId = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $recoveryKey = new PasskeyRecoveryKey('123e4567-e89b-72d3-a456-426614174001');
        $challengeKey = new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174002');
        $identity = new Identity($identityId, new IdentityName('user'), new Email('user@example.com'), Language::JAPANESE, null, new DateTimeImmutable());
        $user = new PasskeyUser(new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174003'), $identityId);
        /** @var MockInterface&PasskeyRecoverySessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(PasskeyRecoverySessionStorageServiceInterface::class);
        $sessions->shouldReceive('requireValid')->once()->with($recoveryKey)->andReturn(new PasskeyRecoverySession($identityId, $method, new DateTimeImmutable('+10 minutes')));
        $challenge = new RecoveryRegistrationChallenge($challengeKey, new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY'), new WebAuthnOptions('{"challenge":"value"}'), new DateTimeImmutable('+5 minutes'), $identityId, $recoveryKey);
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $challenges */
        $challenges = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $challenges->shouldReceive('consumeRecoveryRegistration')->once()->with($challengeKey, $identityId, $recoveryKey)->andReturn($challenge);
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->with($identityId)->andReturn($identity);
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentityIdentifier')->once()->with($identityId)->andReturn($user);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        /** @var MockInterface&PasskeyCredentialFactoryInterface $factory */
        $factory = Mockery::mock(PasskeyCredentialFactoryInterface::class);
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        /** @var MockInterface&AuthServiceInterface $auth */
        $auth = Mockery::mock(AuthServiceInterface::class);
        /** @var MockInterface&PasskeyRecoveryNotificationServiceInterface $notification */
        $notification = Mockery::mock(PasskeyRecoveryNotificationServiceInterface::class);
        /** @var MockInterface&SecurityEventRecorderInterface $events */
        $events = Mockery::mock(SecurityEventRecorderInterface::class);

        return [
            'identityId' => $identityId, 'recoveryKey' => $recoveryKey, 'identity' => $identity, 'user' => $user,
            'input' => new RecoverPasskeyInput($recoveryKey, $challengeKey, new PasskeyDisplayName('recovered'), '{"response":true}'),
            'sessions' => $sessions, 'challenges' => $challenges, 'identityRepository' => $identityRepository, 'passkeyUserRepository' => $passkeyUserRepository,
            'passkeyCredentialRepository' => $passkeyCredentialRepository, 'factory' => $factory, 'webAuthn' => $webAuthn, 'auth' => $auth,
            'notification' => $notification, 'events' => $events,
        ];
    }

    /** @param RecoveryContext $context */
    private function useCase(array $context): RecoverPasskey
    {
        return new RecoverPasskey($context['sessions'], $context['challenges'], $context['identityRepository'], $context['passkeyUserRepository'], $context['passkeyCredentialRepository'], $context['factory'], $context['webAuthn'], $context['auth'], $context['notification'], $context['events']);
    }
}
