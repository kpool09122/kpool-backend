<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions;

use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AuthenticationChallenge;
use Source\Identity\Application\Service\WebAuthn\AuthenticationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptions;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsInput;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsOutput;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Tests\TestCase;

class CreatePasskeyAuthenticationOptionsTest extends TestCase
{
    private const string CHALLENGE_KEY = '01994e3a-a15e-72d3-a456-426614174000';
    private const string CHALLENGE = 'MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY';

    public function testItIsBound(): void
    {
        $this->bindDependencies();

        $this->assertInstanceOf(
            CreatePasskeyAuthenticationOptions::class,
            $this->app->make(CreatePasskeyAuthenticationOptionsInterface::class),
        );
    }

    public function testItCreatesDiscoverableAuthenticationOptionsAndStoresChallenge(): void
    {
        $options = new WebAuthnOptions('{"challenge":"challenge","rpId":"example.com","allowCredentials":[],"userVerification":"required","timeout":300000}');
        /** @var MockInterface&WebAuthnServiceInterface $webAuthn */
        $webAuthn = Mockery::mock(WebAuthnServiceInterface::class);
        $webAuthn->shouldReceive('createAuthenticationOptions')
            ->once()
            ->with(Mockery::on(static fn (AuthenticationOptionsInput $input): bool =>
                (string) $input->challenge === self::CHALLENGE
                && $input->allowedCredentialIds === []))
            ->andReturn($options);
        /** @var MockInterface&ChallengeSessionStorageServiceInterface $storage */
        $storage = Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        $storage->shouldReceive('storeAuthentication')
            ->once()
            ->with(Mockery::on(static fn (AuthenticationChallenge $stored): bool =>
                (string) $stored->key === self::CHALLENGE_KEY
                && (string) $stored->challenge === self::CHALLENGE
                && $stored->options === $options
                && $stored->expiresAt > new \DateTimeImmutable()));
        $this->bindDependencies($webAuthn, $storage);

        $output = new CreatePasskeyAuthenticationOptionsOutput();
        $this->app->make(CreatePasskeyAuthenticationOptionsInterface::class)->process(
            new CreatePasskeyAuthenticationOptionsInput(),
            $output,
        );

        $this->assertSame(self::CHALLENGE_KEY, $output->toArray()['challengeKey']);
        $this->assertSame([], $output->toArray()['options']['allowCredentials']);
        $this->assertSame('required', $output->toArray()['options']['userVerification']);
    }

    private function bindDependencies(
        ?WebAuthnServiceInterface $webAuthn = null,
        ?ChallengeSessionStorageServiceInterface $storage = null,
    ): void {
        $webAuthn ??= Mockery::mock(WebAuthnServiceInterface::class);
        if ($webAuthn instanceof MockInterface) {
            $webAuthn->shouldReceive('createAuthenticationOptions')->zeroOrMoreTimes()->andReturn(new WebAuthnOptions('{"challenge":"challenge"}'));
        }
        $storage ??= Mockery::mock(ChallengeSessionStorageServiceInterface::class);
        if ($storage instanceof MockInterface) {
            $storage->shouldReceive('storeAuthentication')->zeroOrMoreTimes();
        }
        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')->zeroOrMoreTimes()->andReturn(self::CHALLENGE_KEY);
        $challengeGenerator = Mockery::mock(WebAuthnChallengeGeneratorInterface::class);
        $challengeGenerator->shouldReceive('generate')->zeroOrMoreTimes()->andReturn(new WebAuthnChallenge(self::CHALLENGE));

        $this->app->instance(WebAuthnServiceInterface::class, $webAuthn);
        $this->app->instance(ChallengeSessionStorageServiceInterface::class, $storage);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(WebAuthnChallengeGeneratorInterface::class, $challengeGenerator);
    }
}
