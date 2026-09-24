<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Factory;

use Mockery;
use Source\Identity\Domain\Factory\PasskeyUserFactoryInterface;
use Source\Identity\Infrastructure\Factory\PasskeyUserFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class PasskeyUserFactoryTest extends TestCase
{
    public function testItIsBound(): void
    {
        $this->assertInstanceOf(
            PasskeyUserFactory::class,
            $this->app->make(PasskeyUserFactoryInterface::class),
        );
    }

    public function testItCreatesAUserWithoutAnIdentity(): void
    {
        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')
            ->once()
            ->andReturn('123e4567-e89b-72d3-a456-426614174001');
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);

        $user = $this->app->make(PasskeyUserFactoryInterface::class)->create();

        $this->assertSame('123e4567-e89b-72d3-a456-426614174001', (string) $user->identifier());
        $this->assertNull($user->identityIdentifier());
    }

    public function testItCreatesAUserLinkedToTheSpecifiedIdentity(): void
    {
        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')
            ->once()
            ->andReturn('123e4567-e89b-72d3-a456-426614174001');
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');

        $user = $this->app->make(PasskeyUserFactoryInterface::class)->create($identityIdentifier);

        $this->assertSame($identityIdentifier, $user->identityIdentifier());
    }
}
