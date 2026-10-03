<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Infrastructure\Factory\PrincipalFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PrincipalFactoryTest extends TestCase
{
    public function testCreatesEntityWithGeneratedIdentifier(): void
    {
        $uuid = '019c9b4c-0000-7000-8000-000000000002';
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects($this->once())->method('generate')->willReturn($uuid);
        $factory = new PrincipalFactory($uuidGenerator);
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $entity = $factory->create($identityIdentifier, $accountIdentifier);

        $this->assertSame($uuid, (string) $entity->principalIdentifier());
        $this->assertSame($identityIdentifier, $entity->identityIdentifier());
        $this->assertSame($accountIdentifier, $entity->accountIdentifier());
    }
}
