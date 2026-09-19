<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Factory;

use Mockery;
use Source\Identity\Domain\Factory\PasskeyCredentialFactoryInterface;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Identity\Infrastructure\Factory\PasskeyCredentialFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class PasskeyCredentialFactoryTest extends TestCase
{
    public function testItIsBound(): void
    {
        $this->assertInstanceOf(
            PasskeyCredentialFactory::class,
            $this->app->make(PasskeyCredentialFactoryInterface::class),
        );
    }

    public function testItCreatesANewPasskeyCredential(): void
    {
        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')
            ->once()
            ->andReturn('123e4567-e89b-72d3-a456-426614174001');
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $factory = $this->app->make(PasskeyCredentialFactoryInterface::class);

        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $credentialId = new WebAuthnCredentialId('Y3JlZGVudGlhbC1pZA');
        $credentialSource = new CredentialSource('{"credential":"source"}');
        $displayName = new PasskeyDisplayName('My passkey');

        $credential = $factory->create(
            $identityIdentifier,
            $credentialId,
            $credentialSource,
            1,
            true,
            false,
            ['internal', 'hybrid'],
            $displayName,
        );

        $this->assertSame('123e4567-e89b-72d3-a456-426614174001', (string) $credential->identifier());
        $this->assertSame($identityIdentifier, $credential->identityIdentifier());
        $this->assertSame($credentialId, $credential->credentialId());
        $this->assertSame($credentialSource, $credential->credentialSource());
        $this->assertSame(1, $credential->signCount());
        $this->assertTrue($credential->backupEligible());
        $this->assertFalse($credential->backupState());
        $this->assertSame(['internal', 'hybrid'], $credential->transports());
        $this->assertSame($displayName, $credential->displayName());
        $this->assertNull($credential->lastUsedAt());
    }
}
