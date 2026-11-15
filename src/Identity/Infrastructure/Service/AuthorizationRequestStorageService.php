<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\PendingAuthorization;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationRequestStorageService implements AuthorizationRequestStorageServiceInterface
{
    private const string KEY = 'oauth_authorization_requests';

    public function store(AuthorizationCodeParameters $authorizationCodeParameters, IdentityIdentifier $identityIdentifier): PendingAuthorization
    {
        $session = request()->session();
        $pending = new PendingAuthorization(bin2hex(random_bytes(32)), $authorizationCodeParameters, new DateTimeImmutable('+10 minutes'), $identityIdentifier);
        $session->put(self::KEY, [$pending->identifier => $pending]);

        return $pending;
    }

    public function requireValid(string $identifier, IdentityIdentifier $identityIdentifier): PendingAuthorization
    {
        return $this->requireValidInSession(request()->session(), $identifier, $identityIdentifier);
    }

    public function complete(string $identifier, IdentityIdentifier $identityIdentifier): void
    {
        $session = request()->session();
        $pending = $this->requireValidInSession($session, $identifier, $identityIdentifier);
        $complete = function () use ($session, $pending, $identityIdentifier): void {
            if ($this->latestRequest($session)?->identifier === $pending->identifier) {
                $completed = new PendingAuthorization($pending->identifier, $pending->parameters, $pending->expiresAt, $identityIdentifier, true);
                $session->put(self::KEY, [$completed->identifier => $completed]);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($complete);

            return;
        }

        $complete();
    }

    private function requireValidInSession(Session $session, string $identifier, IdentityIdentifier $identityIdentifier): PendingAuthorization
    {
        $pending = $this->latestRequest($session);
        if ($pending === null || $pending->identifier !== $identifier) {
            throw new AuthorizationRequestUnavailableException();
        }
        if ($pending->expiresAt <= new DateTimeImmutable()) {
            throw new AuthorizationRequestUnavailableException('expired');
        }
        if ($pending->completed) {
            throw new AuthorizationRequestUnavailableException('completed');
        }
        if ($pending->identityIdentifier === null || (string) $pending->identityIdentifier !== (string) $identityIdentifier) {
            throw new AuthorizationRequestUnavailableException();
        }

        return $pending;
    }

    private function latestRequest(Session $session): ?PendingAuthorization
    {
        // Older sessions may contain multiple requests in insertion order.
        /** @var array<string, PendingAuthorization> $entries */
        $entries = $session->get(self::KEY, []);
        $identifier = array_key_last($entries);

        return $identifier === null ? null : $entries[$identifier];
    }
}
