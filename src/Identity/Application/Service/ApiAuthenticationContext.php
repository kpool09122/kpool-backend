<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use DateTimeImmutable;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

/** Authorization envelope, not an evaluated permission for an individual API operation. */
readonly class ApiAuthenticationContext
{
    private string $sourceTokenIdentifier;
    private AuthorizationConnectionIdentifier $connectionIdentifier;
    private IdentityIdentifier $identityIdentifier;
    private OAuthClientIdentifier $clientIdentifier;
    /** @var list<AuthorizationScope> */
    private array $scopes;
    private AccountIdentifier $originalAccountIdentifier;
    private AccountPrincipalIdentifier $originalPrincipalIdentifier;

    public function __construct(ValidatedAuthorizationToken $authorizationToken, private DateTimeImmutable $expiresAt)
    {
        $connection = $authorizationToken->connection;
        $this->sourceTokenIdentifier = $authorizationToken->tokenIdentifier;
        $this->connectionIdentifier = $connection->identifier();
        $this->identityIdentifier = $connection->identityIdentifier();
        $this->clientIdentifier = $connection->clientIdentifier();
        $this->scopes = $authorizationToken->scopes;
        $this->originalAccountIdentifier = $connection->originalAccountIdentifier();
        $this->originalPrincipalIdentifier = $connection->originalPrincipalIdentifier();
    }

    public function sourceTokenIdentifier(): string
    {
        return $this->sourceTokenIdentifier;
    }

    public function connectionIdentifier(): AuthorizationConnectionIdentifier
    {
        return $this->connectionIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function clientIdentifier(): OAuthClientIdentifier
    {
        return $this->clientIdentifier;
    }

    /** @return list<AuthorizationScope> */
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

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
