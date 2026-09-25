<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions;

use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AuthenticationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\StepUpAuthenticationChallenge;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions\CreateStepUpPasskeyOptions;
use Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions\CreateStepUpPasskeyOptionsInput;
use Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions\CreateStepUpPasskeyOptionsInterface;
use Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions\CreateStepUpPasskeyOptionsOutput;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Exception\PasskeyRecoveryRequiredException;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class CreateStepUpPasskeyOptionsTest extends TestCase
{
    private const string IDENTITY_ID = '123e4567-e89b-72d3-a456-426614174001';
    private const string CHALLENGE_KEY = '123e4567-e89b-72d3-a456-426614174002';
    private const string CHALLENGE = 'MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY';

    public function testItIsBound(): void
    {
        $this->bindDependencies([]);

        $this->assertInstanceOf(CreateStepUpPasskeyOptions::class, $this->app->make(CreateStepUpPasskeyOptionsInterface::class));
    }

    public function testItLimitsOptionsToTheAuthenticatedIdentitysCredentialsAndStoresIdentityBoundChallenge(): void
    {
        $first = $this->credential('123e4567-e89b-72d3-a456-426614174010', 'Y3JlZGVudGlhbC0x');
        $second = $this->credential('123e4567-e89b-72d3-a456-426614174011', 'Y3JlZGVudGlhbC0y');
        $options = new WebAuthnOptions('{"challenge":"challenge","allowCredentials":[{"id":"Y3JlZGVudGlhbC0x"},{"id":"Y3JlZGVudGlhbC0y"}]}');

        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('createAuthenticationOptions')->once()->with(Mockery::on(
            static fn (AuthenticationOptionsInput $input): bool => (string) $input->challenge === self::CHALLENGE
                && array_map(static fn (WebAuthnCredentialId $id): string => (string) $id, $input->allowedCredentialIds)
                    === ['Y3JlZGVudGlhbC0x', 'Y3JlZGVudGlhbC0y'],
        ))->andReturn($options);
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('storeStepUpAuthentication')->once()->with(Mockery::on(
            static fn (StepUpAuthenticationChallenge $stored): bool => (string) $stored->key === self::CHALLENGE_KEY
                && (string) $stored->identityIdentifier === self::IDENTITY_ID
                && $stored->options === $options
                && $stored->expiresAt > new \DateTimeImmutable(),
        ));
        $this->bindDependencies([$first, $second], $webAuthn, $storage);

        $output = new CreateStepUpPasskeyOptionsOutput();
        $this->app->make(CreateStepUpPasskeyOptionsInterface::class)->process(
            new CreateStepUpPasskeyOptionsInput(new IdentityIdentifier(self::IDENTITY_ID)),
            $output,
        );

        $this->assertSame(self::CHALLENGE_KEY, $output->toArray()['challengeKey']);
    }

    public function testItDirectsAnIdentityWithoutPasskeysToRecoveryInsteadOfCreatingAChallenge(): void
    {
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldNotReceive('storeStepUpAuthentication');
        $this->bindDependencies([], storage: $storage);

        $this->expectException(PasskeyRecoveryRequiredException::class);
        $this->app->make(CreateStepUpPasskeyOptionsInterface::class)->process(
            new CreateStepUpPasskeyOptionsInput(new IdentityIdentifier(self::IDENTITY_ID)),
            new CreateStepUpPasskeyOptionsOutput(),
        );
    }

    /** @param PasskeyCredential[] $credentials */
    private function bindDependencies(
        array $credentials,
        ?WebAuthnServiceInterface $webAuthn = null,
        ?ChallengeSessionStorageServiceInterface $storage = null,
    ): void {
        $repository = Mockery::mock(PasskeyCredentialRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifier')->zeroOrMoreTimes()->with(Mockery::on(
            static fn (IdentityIdentifier $id): bool => (string) $id === self::IDENTITY_ID,
        ))->andReturn($credentials);
        $webAuthn ??= Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldIgnoreMissing();
        $storage ??= Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldIgnoreMissing();
        $challengeGenerator = Mockery::mock(WebAuthnChallengeGeneratorInterface::class);
        $challengeGenerator->shouldReceive('generate')->zeroOrMoreTimes()->andReturn(new WebAuthnChallenge(self::CHALLENGE));
        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')->zeroOrMoreTimes()->andReturn(self::CHALLENGE_KEY);

        $this->app->instance(PasskeyCredentialRepositoryInterface::class, $repository);
        $this->app->instance(WebAuthnServiceInterface::class, $webAuthn);
        $this->app->instance(ChallengeSessionStorageServiceInterface::class, $storage);
        $this->app->instance(WebAuthnChallengeGeneratorInterface::class, $challengeGenerator);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
    }

    private function credential(string $identifier, string $credentialId): PasskeyCredential
    {
        return new PasskeyCredential(
            new PasskeyCredentialIdentifier($identifier),
            new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174020'),
            new WebAuthnCredentialId($credentialId),
            new CredentialSource('{"credential":"source"}'),
            0,
            false,
            false,
            ['internal'],
            new PasskeyDisplayName('Passkey'),
            null,
        );
    }
}
