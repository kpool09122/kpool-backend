<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Factory;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Infrastructure\Factory\ArchivedPrincipalFactory;

class ArchivedPrincipalFactoryTest extends TestCase
{
    public function testCreatesEntityWithGeneratedIdentifier(): void
    {
        $uuid = '019c9b4c-0000-7000-8000-000000000002';
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects($this->once())->method('generate')->willReturn($uuid);
        $factory = new ArchivedPrincipalFactory($uuidGenerator);
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $principalType = ArchivedPrincipalType::ACCOUNT;
        $principalId = 'principalId-value';
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $before = new DateTimeImmutable();
        $entity = $factory->create($identityIdentifier, $principalType, $principalId, $accountIdentifier);
        $after = new DateTimeImmutable();

        $this->assertSame($uuid, (string) $entity->archivedPrincipalIdentifier());
        $this->assertSame($identityIdentifier, $entity->identityIdentifier());
        $this->assertSame($principalType, $entity->principalType());
        $this->assertSame($principalId, $entity->principalId());
        $this->assertSame($accountIdentifier, $entity->accountIdentifier());
        $this->assertGreaterThanOrEqual($before, $entity->archivedAt());
        $this->assertLessThanOrEqual($after, $entity->archivedAt());
    }
}
