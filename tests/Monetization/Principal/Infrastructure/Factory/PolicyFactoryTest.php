<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Effect;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Principal\Domain\ValueObject\Statement;
use Source\Monetization\Principal\Infrastructure\Factory\PolicyFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class PolicyFactoryTest extends TestCase
{
    public function testPreservesAccountScopedValues(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000002');
        $statement = new Statement(Effect::DENY, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::PRINCIPAL_GROUP]);
        $entity = (new PolicyFactory($generator))->create('deny', [$statement], $account);
        self::assertSame($account, $entity->accountIdentifier());
        self::assertSame([$statement], $entity->statements());
        self::assertFalse($entity->isSystemPolicy());
    }

    public function testFactoryUsesGeneratedIdentifierAndPreservesInput(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $entity = (new PolicyFactory($generator))->create('test', []);
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) $entity->policyIdentifier());
        self::assertSame('test', $entity->name());
    }
}
