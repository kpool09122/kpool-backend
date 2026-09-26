<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Service;

use Illuminate\Support\Facades\Redis;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Throwable;

class CurrentAccountService implements CurrentAccountServiceInterface
{
    private const string KEY_PREFIX = 'current-account:';

    public function find(IdentityIdentifier $identityIdentifier): ?CurrentAccount
    {
        try {
            $value = Redis::get($this->key($identityIdentifier));
            if (! is_string($value) || $value === '') {
                return null;
            }
            $payload = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }
        if (! is_array($payload) || ! $this->isValidPayload($payload)) {
            return null;
        }

        return new CurrentAccount(
            originalIdentityIdentifier: new IdentityIdentifier($payload['originalIdentityIdentifier']),
            originalAccountIdentifier: new AccountIdentifier($payload['originalAccountIdentifier']),
            originalPrincipalIdentifier: new PrincipalIdentifier($payload['originalPrincipalIdentifier']),
            effectiveAccountIdentifier: new AccountIdentifier($payload['effectiveAccountIdentifier']),
            effectivePrincipalIdentifier: new PrincipalIdentifier($payload['effectivePrincipalIdentifier']),
            delegationIdentifier: is_string($payload['delegationIdentifier']) ? new DelegationIdentifier($payload['delegationIdentifier']) : null,
        );
    }

    public function save(CurrentAccount $currentAccount): void
    {
        Redis::set($this->key($currentAccount->originalIdentityIdentifier), json_encode([
            'originalIdentityIdentifier' => (string) $currentAccount->originalIdentityIdentifier,
            'originalAccountIdentifier' => (string) $currentAccount->originalAccountIdentifier,
            'originalPrincipalIdentifier' => (string) $currentAccount->originalPrincipalIdentifier,
            'effectiveAccountIdentifier' => (string) $currentAccount->effectiveAccountIdentifier,
            'effectivePrincipalIdentifier' => (string) $currentAccount->effectivePrincipalIdentifier,
            'delegationIdentifier' => $currentAccount->delegationIdentifier !== null ? (string) $currentAccount->delegationIdentifier : null,
        ], JSON_THROW_ON_ERROR));
    }

    public function forget(IdentityIdentifier $identityIdentifier): void
    {
        Redis::del($this->key($identityIdentifier));
    }

    private function key(IdentityIdentifier $identityIdentifier): string
    {
        $request = request();
        $sessionId = $request->hasSession() ? $request->session()->getId() : 'stateless';

        return self::KEY_PREFIX . $identityIdentifier . ':' . $sessionId;
    }

    /** @param array<string, mixed> $payload */
    private function isValidPayload(array $payload): bool
    {
        return is_string($payload['originalIdentityIdentifier'] ?? null)
            && is_string($payload['originalAccountIdentifier'] ?? null)
            && is_string($payload['originalPrincipalIdentifier'] ?? null)
            && is_string($payload['effectiveAccountIdentifier'] ?? null)
            && is_string($payload['effectivePrincipalIdentifier'] ?? null)
            && array_key_exists('delegationIdentifier', $payload)
            && (is_string($payload['delegationIdentifier']) || $payload['delegationIdentifier'] === null);
    }
}
