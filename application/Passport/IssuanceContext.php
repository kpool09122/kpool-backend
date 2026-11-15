<?php

declare(strict_types=1);

namespace Application\Passport;

use Laravel\Passport\Passport;
use League\OAuth2\Server\Exception\OAuthServerException;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

/** Created for each library invocation, never retained in a singleton. */
class IssuanceContext
{
    public ?AuthorizationConsent $consent = null;

    public function __construct(
        private readonly AuthorizationConsentRepositoryInterface $authorizationConsentRepository,
        public readonly ?string $requestedResource,
    ) {
    }

    public function load(AuthorizationConsentIdentifier $identifier): bool
    {
        $this->consent = $this->authorizationConsentRepository->find($identifier);

        return $this->consent !== null && $this->consent->revokedAt() === null
            && $this->consent->resource() === config()->string('mcp_oauth.resource')
            && Passport::client()->newQuery()->whereKey((string) $this->consent->clientIdentifier())->where('revoked', false)->exists();
    }

    /** @param array<array-key, string> $scopes */
    public function requireConsent(?string $identity, string $client, array $scopes): AuthorizationConsent
    {
        $consent = $this->consent;
        if ($consent === null || ! $this->load($consent->identifier())
            || $identity !== (string) $consent->identityIdentifier() || $client !== (string) $consent->clientIdentifier()) {
            throw OAuthServerException::invalidGrant('Authorization consent is invalid.');
        }
        if ($this->requestedResource !== null && $this->requestedResource !== $consent->resource()) {
            throw OAuthServerException::invalidRequest('resource');
        }
        foreach ($scopes as $scope) {
            if (! in_array(AuthorizationScope::tryFrom($scope), $consent->scopes(), true)) {
                throw OAuthServerException::invalidScope($scope);
            }
        }

        return $consent;
    }
}
