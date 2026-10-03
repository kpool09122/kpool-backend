<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Factory;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Infrastructure\Factory\ArchivedAccountFactory;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class ArchivedAccountFactoryTest extends TestCase
{
    public function testCreatesEntityWithGeneratedIdentifier(): void
    {
        $uuid = '019c9b4c-0000-7000-8000-000000000002';
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects($this->once())->method('generate')->willReturn($uuid);
        $factory = new ArchivedAccountFactory($uuidGenerator);
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $accountCategory = AccountCategory::AGENCY;
        $accountType = AccountType::CORPORATION;
        $before = new DateTimeImmutable();
        $entity = $factory->create($accountIdentifier, $accountCategory, $accountType);
        $after = new DateTimeImmutable();

        $this->assertSame($uuid, (string) $entity->archivedAccountIdentifier());
        $this->assertSame($accountIdentifier, $entity->accountIdentifier());
        $this->assertSame($accountCategory, $entity->accountCategory());
        $this->assertSame($accountType, $entity->accountType());
        $this->assertGreaterThanOrEqual($before, $entity->archivedAt());
        $this->assertLessThanOrEqual($after, $entity->archivedAt());
    }
}
