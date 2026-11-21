<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AuthContextCache;
use Application\Http\Context\MonetizationContext;
use Application\Http\Context\MonetizationPrincipalResolver;
use Application\Http\Middleware\ResolveMonetizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Psr\Log\NullLogger;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ResolveMonetizationContextTest extends TestCase
{
    public function testBindsOriginalIdentityPrincipalForEffectiveAccount(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $context = new AccountContext(
            new AccountPrincipal(new AccountPrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $account),
            AccountType::CORPORATION,
            AccountStatus::ACTIVE,
            AccountCategory::AGENCY,
            originalIdentityIdentifier: $identity,
        );
        app()->instance(AccountContext::class, $context);
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identity, $account, PrincipalStatus::SUSPENDED);
        $repository = $this->createMock(PrincipalRepositoryInterface::class);
        $repository->expects(self::once())->method('findByIdentityIdentifierAndAccountIdentifier')->with($identity, $account)->willReturn($principal);
        Redis::shouldReceive('get')->once()->with('auth-context:monetization:' . $identity . ':' . $account)->andReturnNull();
        Redis::shouldReceive('setex')->once();
        $middleware = new ResolveMonetizationContext(new MonetizationPrincipalResolver($repository), new AuthContextCache(new NullLogger()));
        $response = $middleware->handle(Request::create('/api/v1/monetization/test'), fn () => response('ok'));
        self::assertSame('ok', $response->getContent());
        self::assertSame($principal->principalIdentifier(), app(MonetizationContext::class)->principalIdentifier);
    }
}
