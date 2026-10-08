<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier as SiteManagementPrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier as WikiPrincipalIdentifier;
use Throwable;

class AuthContextCache
{
    private const int TTL_SECONDS = 3600;
    private const string ACTOR_KEY_PREFIX = 'auth-context:actor:';
    private const string ACCOUNT_KEY_PREFIX = 'auth-context:account:';
    private const string WIKI_KEY_PREFIX = 'auth-context:wiki:';
    private const string SITE_MANAGEMENT_KEY_PREFIX = 'auth-context:site-management:';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

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
        ]);

        return $context;
    }

    /**
     * @param callable(): AccountContext $dbResolver
     * @throws AccountNotFoundException
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
        $principal = $context->principal();
        $this->write($this->accountKey($identityIdentifier), [
            'principalIdentifier' => (string) $principal->principalIdentifier(),
            'identityIdentifier' => (string) $principal->identityIdentifier(),
            'accountIdentifier' => (string) $principal->accountIdentifier(),
            'accountType' => $context->nullableAccountType()?->value,
            'accountStatus' => $context->accountStatus()->value,
            'originalAccountStatus' => $context->originalAccountStatus()->value,
            'accountCategory' => $context->accountCategory()->value,
            'accountPolicies' => $context->accountPolicies(),
            'originalIdentityIdentifier' => (string) $context->originalIdentityIdentifier(),
            'originalAccountIdentifier' => (string) $context->originalAccountIdentifier(),
            'originalPrincipalIdentifier' => (string) $context->originalPrincipalIdentifier(),
            'delegationIdentifier' => $context->delegationIdentifier() !== null
                ? (string) $context->delegationIdentifier()
                : null,
        ]);

        return $context;
    }

    /** @param callable(): WikiContext $dbResolver */
    public function resolveWiki(
        IdentityIdentifier $identityIdentifier,
        AccountIdentifier $accountIdentifier,
        callable $dbResolver,
    ): WikiContext {
        $cached = $this->read($this->wikiKey($identityIdentifier, $accountIdentifier));
        if ($cached !== null) {
            $context = $this->wikiFromPayload($cached);
            if ($context !== null) {
                return $context;
            }
        }

        $context = $dbResolver();
        $this->write($this->wikiKey($identityIdentifier, $accountIdentifier), [
            'principalIdentifier' => (string) $context->principalIdentifier,
        ]);

        return $context;
    }

    /** @param callable(): SiteManagementContext $dbResolver */
    public function resolveSiteManagement(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier, callable $dbResolver): SiteManagementContext
    {
        $cached = $this->read(self::SITE_MANAGEMENT_KEY_PREFIX . $identityIdentifier . ':' . $accountIdentifier);
        if ($cached !== null && is_string($cached['principalIdentifier'] ?? null) && UuidValidator::isValid($cached['principalIdentifier'])) {
            return new SiteManagementContext(new SiteManagementPrincipalIdentifier($cached['principalIdentifier']));
        }

        $context = $dbResolver();
        $this->write(self::SITE_MANAGEMENT_KEY_PREFIX . $identityIdentifier . ':' . $accountIdentifier, [
            'principalIdentifier' => (string) $context->principalIdentifier,
        ]);

        return $context;
    }

    public function forgetSiteManagement(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): void
    {
        $this->delete(self::SITE_MANAGEMENT_KEY_PREFIX . $identityIdentifier . ':' . $accountIdentifier);
    }

    public function forgetActor(IdentityIdentifier $identityIdentifier): void
    {
        $this->delete($this->actorKey($identityIdentifier));
    }

    public function forgetAccount(IdentityIdentifier $identityIdentifier): void
    {
        $this->delete($this->accountKey($identityIdentifier));
    }

    public function forgetWiki(IdentityIdentifier $identityIdentifier, ?AccountIdentifier $accountIdentifier = null): void
    {
        if ($accountIdentifier !== null) {
            $this->delete($this->wikiKey($identityIdentifier, $accountIdentifier));

            return;
        }

        $this->delete(self::WIKI_KEY_PREFIX . $identityIdentifier);
        foreach ($this->keys(self::WIKI_KEY_PREFIX . $identityIdentifier . ':*') as $key) {
            $this->delete($key);
        }
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

    private function wikiKey(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): string
    {
        return self::WIKI_KEY_PREFIX . $identityIdentifier . ':' . $accountIdentifier;
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
        } catch (Throwable $exception) {
            $this->logger->warning('Authentication context cache invalidation failed.', ['exception' => $exception]);
        }
    }

    /**
     * @return string[]
     */
    private function keys(string $pattern): array
    {
        try {
            $keys = Redis::keys($pattern);
        } catch (Throwable $exception) {
            $this->logger->warning('Authentication context cache enumeration failed.', ['exception' => $exception]);

            return [];
        }

        return is_array($keys) ? array_values(array_filter($keys, 'is_string')) : [];
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
        );
    }

    /** @param array<string, mixed> $payload */
    private function accountFromPayload(array $payload): ?AccountContext
    {
        if (
            ! is_string($payload['principalIdentifier'] ?? null)
            || ! is_string($payload['identityIdentifier'] ?? null)
            || ! is_string($payload['accountIdentifier'] ?? null)
            || (! is_string($payload['accountType'] ?? null) && ($payload['accountType'] ?? null) !== null)
            || ! is_string($payload['accountStatus'] ?? null)
            || ! is_string($payload['originalAccountStatus'] ?? null)
            || ! is_string($payload['accountCategory'] ?? null)
            || ! is_array($payload['accountPolicies'] ?? null)
            || ! is_string($payload['originalIdentityIdentifier'] ?? null)
            || ! is_string($payload['originalAccountIdentifier'] ?? null)
            || ! is_string($payload['originalPrincipalIdentifier'] ?? null)
            || (! is_string($payload['delegationIdentifier'] ?? null) && ($payload['delegationIdentifier'] ?? null) !== null)
        ) {
            return null;
        }

        $accountType = is_string($payload['accountType']) ? AccountType::tryFrom($payload['accountType']) : null;
        $accountStatus = AccountStatus::tryFrom($payload['accountStatus']);
        $originalAccountStatus = AccountStatus::tryFrom($payload['originalAccountStatus']);
        $accountCategory = AccountCategory::tryFrom($payload['accountCategory']);
        if ($accountStatus === null || $originalAccountStatus === null || $accountCategory === null) {
            return null;
        }

        return new AccountContext(
            principal: new AccountPrincipal(
                new AccountPrincipalIdentifier($payload['principalIdentifier']),
                new IdentityIdentifier($payload['identityIdentifier']),
                new AccountIdentifier($payload['accountIdentifier']),
            ),
            accountType: $accountType,
            accountStatus: $accountStatus,
            accountCategory: $accountCategory,
            accountPolicies: $payload['accountPolicies'],
            originalIdentityIdentifier: new IdentityIdentifier($payload['originalIdentityIdentifier']),
            originalAccountIdentifier: new AccountIdentifier($payload['originalAccountIdentifier']),
            originalPrincipalIdentifier: new AccountPrincipalIdentifier($payload['originalPrincipalIdentifier']),
            delegationIdentifier: is_string($payload['delegationIdentifier'])
                ? new DelegationIdentifier($payload['delegationIdentifier'])
                : null,
            originalAccountStatus: $originalAccountStatus,
        );
    }

    /** @param array<string, mixed> $payload */
    private function wikiFromPayload(array $payload): ?WikiContext
    {
        if (! is_string($payload['principalIdentifier'] ?? null)) {
            return null;
        }

        return new WikiContext(new WikiPrincipalIdentifier($payload['principalIdentifier']));
    }
}
