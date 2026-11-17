<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class AuthorizationConnection
{
    /**
     * @param list<AuthorizationScope> $scopes
     */
    public function __construct(
        private readonly AuthorizationConnectionIdentifier $identifier,
        private readonly IdentityIdentifier $identityIdentifier,
        private readonly OAuthClientIdentifier $clientIdentifier,
        private readonly array $scopes,
        private readonly AccountIdentifier $originalAccountIdentifier,
        private readonly AccountPrincipalIdentifier $originalPrincipalIdentifier,
        private readonly string $resource,
        private readonly DateTimeImmutable $consentedAt,
        private ?DateTimeImmutable $revokedAt = null,
    ) {
        $scopeValues = array_map(static fn (AuthorizationScope $scope): string => $scope->value, $scopes);
        if ($resource === '' || $scopes === [] || count(array_unique($scopeValues)) !== count($scopes)) {
            throw new InvalidArgumentException('Invalid authorization connection.');
        }
    }

    public function identifier(): AuthorizationConnectionIdentifier
    {
        return $this->identifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function clientIdentifier(): OAuthClientIdentifier
    {
        return $this->clientIdentifier;
    }

    /**
     * @return list<AuthorizationScope>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function originalAccountIdentifier(): AccountIdentifier
    {
        return $this->originalAccountIdentifier;
    }

    public function originalPrincipalIdentifier(): AccountPrincipalIdentifier
    {
        return $this->originalPrincipalIdentifier;
    }

    public function resource(): string
    {
        return $this->resource;
    }

    public function consentedAt(): DateTimeImmutable
    {
        return $this->consentedAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function revoke(DateTimeImmutable $revokedAt): void
    {
        $this->revokedAt ??= $revokedAt;
    }
}
