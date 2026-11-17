<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Factory;

use DateTimeImmutable;
use Mockery;
use Source\Identity\Domain\Factory\AuthorizationConnectionFactoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class AuthorizationConnectionFactoryTest extends TestCase
{
    public function testItCreatesConnectionWithAGeneratedIdentifier(): void
    {
        $uuid = '0199f700-0000-7000-8000-000000000001';
        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')->once()->andReturn($uuid);
        $this->app()->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $authorizationConnectionFactory = $this->app()->make(AuthorizationConnectionFactoryInterface::class);

        $account = new AccountIdentifier('0199f700-0000-7000-8000-000000000002');
        $consentedAt = new DateTimeImmutable('2026-11-14T12:00:00+00:00');
        $connection = $authorizationConnectionFactory->create(
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000003'),
            new OAuthClientIdentifier('0199f700-0000-7000-8000-000000000004'),
            [AuthorizationScope::WikiRead],
            $account,
            new AccountPrincipalIdentifier('0199f700-0000-7000-8000-000000000005'),
            'https://mcp.example.test/mcp',
            $consentedAt,
        );
        self::assertSame($uuid, (string) $connection->identifier());
        self::assertSame($consentedAt, $connection->consentedAt());
        self::assertNull($connection->revokedAt());
    }
}
