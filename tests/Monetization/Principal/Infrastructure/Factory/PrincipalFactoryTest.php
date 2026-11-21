<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Infrastructure\Factory\PrincipalFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PrincipalFactoryTest extends TestCase
{
    public function testPreservesAccountScopedValues(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $identity = new IdentityIdentifier('00000000-0000-7000-8000-000000000001');
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000002');
        $entity = (new PrincipalFactory($generator))->create($identity, $account);
        self::assertSame($identity, $entity->identityIdentifier());
        self::assertSame($account, $entity->accountIdentifier());
    }

    public function testFactoryUsesGeneratedIdentifierAndPreservesInput(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $entity = (new PrincipalFactory($generator))->create(new IdentityIdentifier('69200000-0000-7000-8000-000000000099'), new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        self::assertSame(PrincipalStatus::ACTIVE, $entity->status());
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) $entity->principalIdentifier());
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) $entity->identityIdentifier());
    }
}
