<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Illuminate\Support\Facades\Redis;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Throwable;

class AuthContextCache
{
    private const int TTL_SECONDS = 3600;
    private const string ACTOR_KEY_PREFIX = 'auth-context:actor:';
    private const string ACCOUNT_KEY_PREFIX = 'auth-context:account:';
    private const string WIKI_KEY_PREFIX = 'auth-context:wiki:';

    /** @param callable(): ActorContext $dbResolver */
    public function resolveActor(IdentityIdentifier $identityIdentifier, callable $dbResolver): ActorContext
    {
        $cached = $this->read($this->actorKey($identityIdentifier));
        if ($cached !== null) {
            $context = $this->actorFromPayload($cached);
            if ($context !== null) {
                return $context;
            }
        }

        $context = $dbResolver();
        $this->write($this->actorKey($identityIdentifier), [
            'identityIdentifier' => (string) $context->identityIdentifier,
            'language' => $context->language->value,
            'delegationIdentifier' => $context->delegationIdentifier !== null ? (string) $context->delegationIdentifier : null,
            'originalIdentityIdentifier' => $context->originalIdentityIdentifier !== null ? (string) $context->originalIdentityIdentifier : null,
        ]);

        return $context;
    }

    /**
     * @param callable(): AccountContext $dbResolver
     * @throws \Source\Account\Account\Application\Exception\AccountNotFoundException
     */
    public function resolveAccount(IdentityIdentifier $identityIdentifier, callable $dbResolver): AccountContext
    {
        $cached = $this->read($this->accountKey($identityIdentifier));
        if ($cached !== null) {
            $context = $this->accountFromPayload($cached);
            if ($context !== null) {
                return $context;
            }
        }

        $context = $dbResolver();
        $this->write($this->accountKey($identityIdentifier), [
            'accountIdentifier' => (string) $context->accountIdentifier,
            'role' => $context->role->value,
        ]);

        return $context;
    }

    /** @param callable(): WikiContext $dbResolver */
    public function resolveWiki(IdentityIdentifier $identityIdentifier, callable $dbResolver): WikiContext
    {
        $cached = $this->read($this->wikiKey($identityIdentifier));
        if ($cached !== null) {
            $context = $this->wikiFromPayload($cached);
            if ($context !== null) {
                return $context;
            }
        }

        $context = $dbResolver();
        $this->write($this->wikiKey($identityIdentifier), [
            'principalIdentifier' => (string) $context->principalIdentifier,
        ]);

        return $context;
    }

    public function forgetActor(IdentityIdentifier $identityIdentifier): void
    {
        $this->delete($this->actorKey($identityIdentifier));
    }

    public function forgetAccount(IdentityIdentifier $identityIdentifier): void
    {
        $this->delete($this->accountKey($identityIdentifier));
    }

    public function forgetWiki(IdentityIdentifier $identityIdentifier): void
    {
        $this->delete($this->wikiKey($identityIdentifier));
    }

    /** @param IdentityIdentifier[] $identityIdentifiers */
    public function forgetAccounts(array $identityIdentifiers): void
    {
        foreach ($identityIdentifiers as $identityIdentifier) {
            $this->forgetAccount($identityIdentifier);
        }
    }

    /** @param IdentityIdentifier[] $identityIdentifiers */
    public function forgetWikis(array $identityIdentifiers): void
    {
        foreach ($identityIdentifiers as $identityIdentifier) {
            $this->forgetWiki($identityIdentifier);
        }
    }

    private function actorKey(IdentityIdentifier $identityIdentifier): string
    {
        return self::ACTOR_KEY_PREFIX . $identityIdentifier;
    }

    private function accountKey(IdentityIdentifier $identityIdentifier): string
    {
        return self::ACCOUNT_KEY_PREFIX . $identityIdentifier;
    }

    private function wikiKey(IdentityIdentifier $identityIdentifier): string
    {
        return self::WIKI_KEY_PREFIX . $identityIdentifier;
    }

    /** @return ?array<string, mixed> */
    private function read(string $key): ?array
    {
        try {
            $value = Redis::get($key);
        } catch (Throwable) {
            return null;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $payload = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    /** @param array<string, mixed> $payload */
    private function write(string $key, array $payload): void
    {
        try {
            Redis::setex($key, self::TTL_SECONDS, json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (Throwable) {
            // Redis is a best-effort cache. Requests must keep using DB fallback when writes fail.
        }
    }

    private function delete(string $key): void
    {
        try {
            Redis::del($key);
        } catch (Throwable) {
            // Cache invalidation is best effort; DB state remains source of truth.
        }
    }

    /** @param array<string, mixed> $payload */
    private function actorFromPayload(array $payload): ?ActorContext
    {
        if (! is_string($payload['identityIdentifier'] ?? null) || ! is_string($payload['language'] ?? null)) {
            return null;
        }

        $language = Language::tryFrom($payload['language']);
        if ($language === null) {
            return null;
        }

        return new ActorContext(
            identityIdentifier: new IdentityIdentifier($payload['identityIdentifier']),
            language: $language,
            delegationIdentifier: is_string($payload['delegationIdentifier'] ?? null)
                ? new DelegationIdentifier($payload['delegationIdentifier'])
                : null,
            originalIdentityIdentifier: is_string($payload['originalIdentityIdentifier'] ?? null)
                ? new IdentityIdentifier($payload['originalIdentityIdentifier'])
                : null,
        );
    }

    /** @param array<string, mixed> $payload */
    private function accountFromPayload(array $payload): ?AccountContext
    {
        if (! is_string($payload['accountIdentifier'] ?? null) || ! is_string($payload['role'] ?? null)) {
            return null;
        }

        $role = AccountRole::tryFrom($payload['role']);
        if ($role === null) {
            return null;
        }

        return new AccountContext(
            accountIdentifier: new AccountIdentifier($payload['accountIdentifier']),
            role: $role,
        );
    }

    /** @param array<string, mixed> $payload */
    private function wikiFromPayload(array $payload): ?WikiContext
    {
        if (! is_string($payload['principalIdentifier'] ?? null)) {
            return null;
        }

        return new WikiContext(new PrincipalIdentifier($payload['principalIdentifier']));
    }
}
