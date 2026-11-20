<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Monetization\Principal\Infrastructure\Factory\PrincipalGroupFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class PrincipalGroupFactoryTest extends TestCase
{
    public function testPreservesAccountScopedValues(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000002');
        $role = new RoleIdentifier('00000000-0000-7000-8000-000000000003');
        $entity = (new PrincipalGroupFactory($generator))->create('Default', [$role], $account, true);
        self::assertSame($account, $entity->accountIdentifier());
        self::assertSame([$role], $entity->roles());
        self::assertTrue($entity->isDefault());
        self::assertSame([], $entity->members());
        self::assertNull($entity->description());
    }

    public function testFactoryUsesGeneratedIdentifierAndPreservesInput(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $entity = (new PrincipalGroupFactory($generator))->create('test', [], new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) $entity->principalGroupIdentifier());
        self::assertSame('test', $entity->name());
    }
}
