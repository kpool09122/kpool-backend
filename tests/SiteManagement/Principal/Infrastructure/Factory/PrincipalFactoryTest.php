<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Infrastructure\Factory\PrincipalFactory;

class PrincipalFactoryTest extends TestCase
{
    public function testFactoryUsesGeneratedIdentifierAndPreservesInput(): void
    {
        $generator = $this->createMock(UuidGeneratorInterface::class);
        $generator->expects(self::once())->method('generate')->willReturn('69200000-0000-7000-8000-000000000099');
        $entity = (new PrincipalFactory($generator))->create(new IdentityIdentifier('69200000-0000-7000-8000-000000000099'), new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) $entity->principalIdentifier());
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) $entity->identityIdentifier());
    }
}
