<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class ValidatedAuthorizationTokenTest extends TestCase
{
    public function testItPreservesVerifiedTokenAndConnection(): void
    {
        $account = new AccountIdentifier('0199f700-0000-7000-8000-000000000001');
        $connection = new AuthorizationConnection(
            '0199f700-0000-7000-8000-000000000002',
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000003'),
            'client',
            ['wiki:read'],
            [new AuthorizationTarget($account)],
            $account,
            new PrincipalIdentifier('0199f700-0000-7000-8000-000000000004'),
            $account,
            'https://mcp.example/mcp',
            new DateTimeImmutable()
        );
        $expires = new DateTimeImmutable('+1 hour');
        $token = new ValidatedAuthorizationToken('token', $connection, ['wiki:read'], $expires);
        $this->assertSame('token', $token->tokenIdentifier);
        $this->assertSame($connection, $token->connection);
        $this->assertSame(['wiki:read'], $token->scopes);
        $this->assertSame($expires, $token->expiresAt);
    }
}
