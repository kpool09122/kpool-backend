<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Infrastructure\Factory\RoleFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class RoleFactoryTest extends TestCase
{
    public function testPreservesAccountScopedValues(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000002');
        $policy = new PolicyIdentifier('00000000-0000-7000-8000-000000000003');
        $entity = (new RoleFactory($generator))->create('manager', [$policy], $account);
        self::assertSame($account, $entity->accountIdentifier());
        self::assertSame([$policy], $entity->policies());
        self::assertFalse($entity->isSystemRole());
    }

    public function testFactoryUsesGeneratedIdentifierAndPreservesInput(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $entity = (new RoleFactory($generator))->create('test', []);
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) $entity->roleIdentifier());
        self::assertSame('test', $entity->name());
    }
}
