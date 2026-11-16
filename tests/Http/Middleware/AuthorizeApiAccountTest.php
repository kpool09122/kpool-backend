<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Middleware\AuthorizeApiAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\CreateApiAuthenticationContext;
use Tests\Helper\CreateDelegatedApiAuthenticationContext;
use Tests\TestCase;

class AuthorizeApiAccountTest extends TestCase
{
    public function testCookieAndAuxiliaryOperationsDoNotRequireAValidSelectedAccount(): void
    {
        $middleware = $this->app()->make(AuthorizeApiAccount::class);
        foreach ([['GET', '/api/v1/identity/auth/me'], ['POST', '/api/v1/account/accounts/switch']] as [$method, $path]) {
            $request = Request::create($path, $method);
            $request->attributes->set(ApiAuthenticationContext::class, CreateApiAuthenticationContext::create());
            self::assertSame('allowed', $middleware->handle($request, static fn () => response('allowed'))->getContent());
        }
        self::assertSame('cookie', $middleware->handle(Request::create('/api/v1/wiki/wikis/ja'), static fn () => response('cookie'))->getContent());
    }

    /** @return array<string, array{string}> */
    public static function invalidSelections(): array
    {
        return array_combine(['delegation', 'original-principal', 'target-principal', 'account', 'original-account', 'membership', 'deny'], array_map(static fn (string $value): array => [$value], ['delegation', 'original-principal', 'target-principal', 'account', 'original-account', 'membership', 'deny']));
    }

    #[Group('useDb')]
    #[DataProvider('invalidSelections')]
    public function testBusinessRequestRechecksSelectedAccountAfterAuthorizationChanges(string $change): void
    {
        [$context, $delegation] = CreateDelegatedApiAuthenticationContext::create();
        $request = Request::create('/api/v1/wiki/wikis/ja');
        $request->attributes->set(ApiAuthenticationContext::class, $context);
        $this->app()->instance('request', $request);
        CreateDelegatedApiAuthenticationContext::switchTo($context, new DelegationIdentifier($delegation->id));
        $middleware = $this->app()->make(AuthorizeApiAccount::class);
        self::assertSame('allowed', $middleware->handle($request, static fn () => response('allowed'))->getContent());
        match ($change) {
            'delegation' => DB::table('account_delegations')->where('id', $delegation->id)->update(['status' => 'rejected', 'rejected_at' => now()]),
            'original-principal' => DB::table('account_principals')->where('id', (string) $context->originalPrincipalIdentifier())->update(['status' => 'suspended']),
            'target-principal' => DB::table('account_principals')->where('identity_id', (string) $context->identityIdentifier())->where('account_id', $delegation->delegator_account_id)->update(['status' => 'forced_stop']),
            'account' => DB::table('accounts')->where('id', $delegation->delegator_account_id)->update(['status' => 'suspended']),
            'original-account' => DB::table('accounts')->where('id', (string) $context->originalAccountIdentifier())->update(['status' => 'suspended']),
            'membership' => DB::table('account_principal_group_memberships')->where('principal_id', (string) $context->originalPrincipalIdentifier())->delete(),
            'deny' => DB::table('account_policies')->where('name', 'switch')->where('account_id', (string) $context->originalAccountIdentifier())->update(['statements' => json_encode([['effect' => 'deny', 'actions' => ['account:delegation-account:switch'], 'resource_types' => ['account'], 'condition' => null]])]),
            default => self::fail('Unexpected mutation'),
        };
        $this->expectException(ForbiddenHttpException::class);
        $middleware->handle($request, static fn () => response('must not run'));
    }
}
