<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Factory\AuthorizationConnectionFactory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;

class CreateApiAuthenticationContext
{
    public static function fromConnection(AuthorizationConnection $connection, string $tokenIdentifier = 'source-token'): ApiAuthenticationContext
    {
        $expires = new DateTimeImmutable('+1 hour');

        return new ApiAuthenticationContext(new ValidatedAuthorizationToken($tokenIdentifier, $connection, $connection->scopes(), $expires), $expires);
    }

    /** @param list<AuthorizationScope> $scopes */
    public static function create(array $scopes = [AuthorizationScope::WikiRead]): ApiAuthenticationContext
    {
        $account = new AccountIdentifier('0199f700-0000-7000-8000-000000000001');
        $connection = new AuthorizationConnectionFactory(new UuidGenerator())->create(
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000003'),
            new OAuthClientIdentifier('0199f700-0000-4000-8000-000000000010'),
            $scopes,
            $account,
            new AccountPrincipalIdentifier('0199f700-0000-7000-8000-000000000004'),
            'https://mcp.example/mcp',
            new DateTimeImmutable(),
        );
        $expires = new DateTimeImmutable('+60 seconds');

        return new ApiAuthenticationContext(new ValidatedAuthorizationToken('source', $connection, $scopes, $expires), $expires);
    }
}
