<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Factory;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Principal\Infrastructure\Factory\PolicyFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class PolicyFactoryTest extends TestCase
{
    public function testCreatesEntityWithGeneratedIdentifier(): void
    {
        $uuid = '019c9b4c-0000-7000-8000-000000000002';
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects($this->once())->method('generate')->willReturn($uuid);
        $factory = new PolicyFactory($uuidGenerator);
        $name = 'name-value';
        $statements = [new Statement(Effect::ALLOW, [Action::cases()[0]], [ResourceType::ACCOUNT])];
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $before = new DateTimeImmutable();
        $entity = $factory->create($name, $statements, $accountIdentifier);
        $after = new DateTimeImmutable();

        $this->assertSame($uuid, (string) $entity->policyIdentifier());
        $this->assertSame($name, $entity->name());
        $this->assertSame($statements, $entity->statements());
        $this->assertSame($accountIdentifier, $entity->accountIdentifier());
        $this->assertGreaterThanOrEqual($before, $entity->createdAt());
        $this->assertLessThanOrEqual($after, $entity->createdAt());
    }
}
