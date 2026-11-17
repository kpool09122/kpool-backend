<?php

declare(strict_types=1);

namespace Application\Passport;

use Application\Models\Identity\McpAccessToken;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Bridge\AccessTokenRepository as PassportAccessTokenRepository;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;

class AccessTokenRepository extends PassportAccessTokenRepository
{
    private ?string $revokedTokenIdentifier = null;

    public function __construct(Dispatcher $events, private readonly IssuanceContext $issuanceContext)
    {
        parent::__construct($events);
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, ?string $userIdentifier = null): AccessTokenEntityInterface
    {
        $connection = $this->issuanceContext->requireConnection(
            $userIdentifier,
            $clientEntity->getIdentifier(),
            array_map(static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(), $scopes),
        );

        return new AccessToken((string) $connection->identityIdentifier() ?: null, $scopes, $clientEntity, $connection->resource(), config()->string('mcp_oauth.issuer'));
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $connection = $this->issuanceContext->requireConnection(
            $accessTokenEntity->getUserIdentifier(),
            $accessTokenEntity->getClient()->getIdentifier(),
            array_map(static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(), $accessTokenEntity->getScopes()),
        );
        Passport::token()->forceFill([
            'id' => $id = $accessTokenEntity->getIdentifier(),
            'user_id' => $userId = $accessTokenEntity->getUserIdentifier(),
            'client_id' => $clientId = $accessTokenEntity->getClient()->getIdentifier(),
            'scopes' => $accessTokenEntity->getScopes(),
            'revoked' => false,
            'expires_at' => $accessTokenEntity->getExpiryDateTime(),
            'authorization_connection_id' => (string) $connection->identifier(),
        ])->save();
        $this->events->dispatch(new AccessTokenCreated($id, $userId, $clientId));
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        $token = McpAccessToken::query()->find($tokenId);

        return $token === null || $token->revoked
            || ! $this->issuanceContext->load(new AuthorizationConnectionIdentifier($token->authorization_connection_id));
    }

    public function revokeAccessToken(string $tokenId): void
    {
        parent::revokeAccessToken($tokenId);
        $this->revokedTokenIdentifier = $tokenId;
    }

    public function revokedTokenIdentifier(): ?string
    {
        return $this->revokedTokenIdentifier;
    }
}
