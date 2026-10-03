<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Infrastructure\Factory\RoleFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class RoleFactoryTest extends TestCase
{
    public function testCreatesEntityWithGeneratedIdentifier(): void
    {
        $uuid = '019c9b4c-0000-7000-8000-000000000002';
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects($this->once())->method('generate')->willReturn($uuid);
        $factory = new RoleFactory($uuidGenerator);
        $name = 'name-value';
        $policies = [new PolicyIdentifier('019c9b4c-0000-7000-8000-000000000003')];
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $entity = $factory->create($name, $policies, $accountIdentifier);

        $this->assertSame($uuid, (string) $entity->roleIdentifier());
        $this->assertSame($name, $entity->name());
        $this->assertSame($policies, $entity->policies());
        $this->assertSame($accountIdentifier, $entity->accountIdentifier());
    }
}
