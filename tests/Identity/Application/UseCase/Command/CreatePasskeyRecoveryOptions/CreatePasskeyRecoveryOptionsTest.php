<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySession;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\RecoveryRegistrationChallenge;
use Source\Identity\Application\Service\WebAuthn\RegistrationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions\CreatePasskeyRecoveryOptions;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions\CreatePasskeyRecoveryOptionsInput;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions\CreatePasskeyRecoveryOptionsOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\PasskeyRecoverySessionInvalidException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class CreatePasskeyRecoveryOptionsTest extends TestCase
{
    private const string RECOVERY_KEY = '123e4567-e89b-72d3-a456-426614174001';
    private const string CHALLENGE_KEY = '123e4567-e89b-72d3-a456-426614174002';

    public function testItCreatesIdentityBoundOptionsForAValidRecoverySession(): void
    {
        $identityId = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $recoveryKey = new PasskeyRecoveryKey(self::RECOVERY_KEY);
        /** @var MockInterface&PasskeyRecoverySessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(PasskeyRecoverySessionStorageServiceInterface::class);
        $sessions->shouldReceive('requireValid')->once()->with($recoveryKey)->andReturn(new PasskeyRecoverySession($identityId, 'email', new DateTimeImmutable('+10 minutes')));
        $identity = new Identity($identityId, new IdentityName('user'), new Email('user@example.com'), Language::JAPANESE, null, new DateTimeImmutable());
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->with($identityId)->andReturn($identity);
        $user = new PasskeyUser(new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174003'), $identityId);
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        $passkeyUserRepository->shouldReceive('findByIdentityIdentifier')->once()->with($identityId)->andReturn($user);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $passkeyCredentialRepository->shouldReceive('findByIdentityIdentifier')->once()->with($identityId)->andReturn([]);
        $challenge = new WebAuthnChallenge('MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY');
        /** @var MockInterface&WebAuthnChallengeGeneratorInterface $challengeGenerator */
        $challengeGenerator = Mockery::mock(WebAuthnChallengeGeneratorInterface::class);
        $challengeGenerator->shouldReceive('generate')->once()->andReturn($challenge);
        $options = new WebAuthnOptions('{"challenge":"recovery"}');
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('createRegistrationOptions')->once()->with(Mockery::on(static fn (RegistrationOptionsInput $input): bool => $input->userHandle === (string) $user->identifier() && $input->excludedCredentialIds === []))->andReturn($options);
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('storeRecoveryRegistration')->once()->with(Mockery::on(static fn (RecoveryRegistrationChallenge $stored): bool => $stored->identityIdentifier === $identityId && $stored->recoveryKey === $recoveryKey));
        /** @var MockInterface&UuidGeneratorInterface $uuid */
        $uuid = Mockery::mock(UuidGeneratorInterface::class);
        $uuid->shouldReceive('generate')->once()->andReturn(self::CHALLENGE_KEY);
        $output = new CreatePasskeyRecoveryOptionsOutput();

        (new CreatePasskeyRecoveryOptions($sessions, $identityRepository, $passkeyUserRepository, $passkeyCredentialRepository, $challengeGenerator, $webAuthn, $storage, $uuid))
            ->process(new CreatePasskeyRecoveryOptionsInput($recoveryKey), $output);

        $this->assertSame(['challengeKey' => self::CHALLENGE_KEY, 'options' => ['challenge' => 'recovery']], $output->toArray());
    }

    public function testItRejectsAnExpiredRecoverySessionBeforeCreatingOptions(): void
    {
        /** @var MockInterface&PasskeyRecoverySessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(PasskeyRecoverySessionStorageServiceInterface::class);
        $sessions->shouldReceive('requireValid')->once()->andThrow(new PasskeyRecoverySessionInvalidException('expired'));
        $this->expectException(PasskeyRecoverySessionInvalidException::class);
        $this->useCaseWithUnusedDependencies($sessions)->process(new CreatePasskeyRecoveryOptionsInput(new PasskeyRecoveryKey(self::RECOVERY_KEY)), new CreatePasskeyRecoveryOptionsOutput());
    }

    public function testItCannotUseASessionForAnotherMissingIdentity(): void
    {
        $other = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174099');
        /** @var MockInterface&PasskeyRecoverySessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(PasskeyRecoverySessionStorageServiceInterface::class);
        $sessions->shouldReceive('requireValid')->once()->andReturn(new PasskeyRecoverySession($other, 'email', new DateTimeImmutable('+10 minutes')));
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->with($other)->andReturnNull();
        $this->expectException(IdentityNotFoundException::class);
        $this->useCaseWithUnusedDependencies($sessions, $identityRepository)->process(new CreatePasskeyRecoveryOptionsInput(new PasskeyRecoveryKey(self::RECOVERY_KEY)), new CreatePasskeyRecoveryOptionsOutput());
    }

    private function useCaseWithUnusedDependencies(PasskeyRecoverySessionStorageServiceInterface $sessions, ?IdentityRepositoryInterface $identityRepository = null): CreatePasskeyRecoveryOptions
    {
        /** @var MockInterface&IdentityRepositoryInterface $unusedIdentityRepository */
        $unusedIdentityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        /** @var MockInterface&PasskeyUserRepositoryInterface $passkeyUserRepository */
        $passkeyUserRepository = Mockery::mock(PasskeyUserRepositoryInterface::class);
        /** @var MockInterface&PasskeyCredentialRepositoryInterface $passkeyCredentialRepository */
        $passkeyCredentialRepository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        /** @var MockInterface&WebAuthnChallengeGeneratorInterface $challenges */
        $challenges = Mockery::mock(WebAuthnChallengeGeneratorInterface::class);
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $challengeStorage */
        $challengeStorage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        /** @var MockInterface&UuidGeneratorInterface $uuid */
        $uuid = Mockery::mock(UuidGeneratorInterface::class);

        return new CreatePasskeyRecoveryOptions($sessions, $identityRepository ?? $unusedIdentityRepository, $passkeyUserRepository, $passkeyCredentialRepository, $challenges, $webAuthn, $challengeStorage, $uuid);
    }
}
