<?php

declare(strict_types=1);

namespace Application\Passport;

use Laravel\Passport\Passport;
use League\OAuth2\Server\Exception\OAuthServerException;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

/** Created for each library invocation, never retained in a singleton. */
class IssuanceContext
{
    public ?AuthorizationConnection $connection = null;

    public function __construct(
        private readonly AuthorizationConnectionRepositoryInterface $authorizationConnectionRepository,
        public readonly ?string $requestedResource,
    ) {
    }

    public function load(AuthorizationConnectionIdentifier $identifier): bool
    {
        $this->connection = $this->authorizationConnectionRepository->find($identifier);

        return $this->connection !== null && $this->connection->revokedAt() === null
            && $this->connection->resource() === config()->string('mcp_oauth.resource')
            && Passport::client()->newQuery()->whereKey((string) $this->connection->clientIdentifier())->where('revoked', false)->exists();
    }

    /** @param array<array-key, string> $scopes */
    public function requireConnection(?string $identity, string $client, array $scopes): AuthorizationConnection
    {
        $connection = $this->connection;
        if ($connection === null || ! $this->load($connection->identifier())
            || $identity !== (string) $connection->identityIdentifier() || $client !== (string) $connection->clientIdentifier()) {
            throw OAuthServerException::invalidGrant('Authorization connection is invalid.');
        }
        if ($this->requestedResource !== null && $this->requestedResource !== $connection->resource()) {
            throw OAuthServerException::invalidRequest('resource');
        }
        foreach ($scopes as $scope) {
            if (! in_array(AuthorizationScope::tryFrom($scope), $connection->scopes(), true)) {
                throw OAuthServerException::invalidScope($scope);
            }
        }

        return $connection;
    }
}
