<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use Application\Http\Context\MonetizationContext;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Infrastructure\Service\MonetizationContextService;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class MonetizationContextServiceTest extends TestCase
{
    public function testPermissionChangeInvalidatesOnlyTheAffectedAccountAfterCommit(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $other = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreateAccount::create((string) $account);
        CreateAccount::create((string) $other);
        $principalId = StrTestHelper::generateUuid();
        DB::table('monetization_principals')->insert(['id' => $principalId, 'identity_id' => (string) $identity, 'account_id' => (string) $account, 'status' => 'active']);
        $cache = $this->app()->make(AuthContextCache::class);
        $old = new MonetizationContext(new PrincipalIdentifier($principalId));
        $fresh = new MonetizationContext(new PrincipalIdentifier(StrTestHelper::generateUuid()));
        $cache->resolveMonetization($identity, $account, fn () => $old);
        $cache->resolveMonetization($identity, $other, fn () => $old);
        (new MonetizationContextService($cache))->forgetForAccount($account);
        self::assertEquals($old, $cache->resolveMonetization($identity, $account, fn () => $fresh));
        DB::commit();

        try {
            self::assertSame($fresh, $cache->resolveMonetization($identity, $account, fn () => $fresh));
            self::assertEquals($old, $cache->resolveMonetization($identity, $other, fn () => $fresh));
        } finally {
            $cache->forgetMonetization($identity);
            DB::table('monetization_principals')->where('id', $principalId)->delete();
            DB::table('accounts')->whereIn('id', [(string) $account, (string) $other])->delete();
            DB::table('identities')->where('id', (string) $identity)->delete();
            DB::beginTransaction();
        }
    }

    public function testWarmCacheIsRetainedOnRollbackAndInvalidatedAfterCommit(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $cache = $this->app()->make(AuthContextCache::class);
        $service = new MonetizationContextService($cache);
        $old = new MonetizationContext(new PrincipalIdentifier(StrTestHelper::generateUuid()));
        $fresh = new MonetizationContext(new PrincipalIdentifier(StrTestHelper::generateUuid()));
        $cache->resolveMonetization($identity, $account, fn () => $old);
        DB::commit();

        try {
            DB::beginTransaction();
            $service->forget($identity);
            self::assertEquals($old, $cache->resolveMonetization($identity, $account, fn () => $fresh));
            DB::rollBack();
            self::assertEquals($old, $cache->resolveMonetization($identity, $account, fn () => $fresh));
            DB::beginTransaction();
            $service->forget($identity);
            self::assertEquals($old, $cache->resolveMonetization($identity, $account, fn () => $fresh));
            DB::commit();
            self::assertSame($fresh, $cache->resolveMonetization($identity, $account, fn () => $fresh));
        } finally {
            $cache->forgetMonetization($identity);
            DB::beginTransaction();
        }
    }
}
