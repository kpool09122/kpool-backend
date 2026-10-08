<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Infrastructure\Factory\PrincipalGroupFactory;

class PrincipalGroupFactoryTest extends TestCase
{
    public function testFactoryUsesGeneratedIdentifierAndPreservesInput(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $entity = (new PrincipalGroupFactory($generator))->create('test', [], new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) $entity->principalGroupIdentifier());
        self::assertSame('test', $entity->name());
    }
}
