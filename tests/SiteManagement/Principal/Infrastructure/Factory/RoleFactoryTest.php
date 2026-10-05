<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\SiteManagement\Principal\Infrastructure\Factory\RoleFactory;

class RoleFactoryTest extends TestCase
{
    public function testFactoryUsesGeneratedIdentifierAndPreservesInput(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $entity = (new RoleFactory($generator))->create('test', []);
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) $entity->roleIdentifier());
        self::assertSame('test', $entity->name());
    }
}
