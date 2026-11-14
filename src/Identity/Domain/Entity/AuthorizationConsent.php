<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class AuthorizationConsent
{
    /**
     * @param list<AuthorizationScope> $scopes
     * @param list<AuthorizationTarget> $targets
     */
    public function __construct(
        private readonly AuthorizationConsentIdentifier $identifier,
        private readonly IdentityIdentifier $identityIdentifier,
        private readonly OAuthClientIdentifier $clientIdentifier,
        private readonly array $scopes,
        private readonly array $targets,
        private readonly AccountIdentifier $originalAccountIdentifier,
        private readonly PrincipalIdentifier $originalPrincipalIdentifier,
        private readonly AccountIdentifier $initialAccountIdentifier,
        private readonly string $resource,
        private readonly DateTimeImmutable $consentedAt,
        private ?DateTimeImmutable $revokedAt = null,
    ) {
        $scopeValues = array_map(static fn (AuthorizationScope $scope): string => $scope->value, $scopes);
        if ($resource === '' || $scopes === [] || count(array_unique($scopeValues)) !== count($scopes)) {
            throw new InvalidArgumentException('Invalid authorization consent.');
        }
        $accounts = [];
        foreach ($targets as $target) {
            $account = (string) $target->accountIdentifier;
            if (in_array($account, $accounts, true)
                || ($target->delegationIdentifier === null && $account !== (string) $originalAccountIdentifier)
                || ($target->delegationIdentifier !== null && $account === (string) $originalAccountIdentifier)) {
                throw new InvalidArgumentException('Invalid authorization target.');
            }
            $accounts[] = $account;
        }
        if (! in_array((string) $initialAccountIdentifier, $accounts, true)) {
            throw new InvalidArgumentException('Initial account is not granted.');
        }
    }

    public function identifier(): AuthorizationConsentIdentifier
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

    /**
     * @return list<AuthorizationTarget>
     */
    public function targets(): array
    {
        return $this->targets;
    }

    public function originalAccountIdentifier(): AccountIdentifier
    {
        return $this->originalAccountIdentifier;
    }

    public function originalPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->originalPrincipalIdentifier;
    }

    public function initialAccountIdentifier(): AccountIdentifier
    {
        return $this->initialAccountIdentifier;
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
