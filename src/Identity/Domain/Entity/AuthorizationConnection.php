<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationConnection
{
    /**
     * @param list<string> $scopes
     * @param list<AuthorizationTarget> $targets
     */
    public function __construct(
        public string $identifier,
        public IdentityIdentifier $identityIdentifier,
        public string $clientIdentifier,
        public array $scopes,
        public array $targets,
        public AccountIdentifier $originalAccountIdentifier,
        public PrincipalIdentifier $originalPrincipalIdentifier,
        public AccountIdentifier $initialAccountIdentifier,
        public string $resource,
        public DateTimeImmutable $consentedAt,
        public ?DateTimeImmutable $revokedAt = null,
    ) {
        if ($identifier === '' || $clientIdentifier === '' || $resource === '' || $scopes === []
            || count(array_unique($scopes)) !== count($scopes)) {
            throw new InvalidArgumentException('Invalid authorization connection.');
        }
        foreach ($scopes as $scope) {
            if (AuthorizationScope::tryFrom($scope) === null) {
                throw new InvalidArgumentException('Unknown authorization scope.');
            }
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
}
