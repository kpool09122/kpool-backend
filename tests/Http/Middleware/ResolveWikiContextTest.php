<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\ActorContext;
use Application\Http\Context\PrincipalResolver;
use Application\Http\Context\WikiContext;
use Application\Http\Middleware\ResolveWikiContext;
use Illuminate\Http\Request;
use Mockery;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ResolveWikiContextTest extends TestCase
{
    public function testBindsWikiContextForEffectiveAccount(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $wikiPrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountContext = new AccountContext(
            new AccountPrincipal(new AccountPrincipalIdentifier(StrTestHelper::generateUuid()), $identityId, $accountId),
            AccountType::CORPORATION,
            AccountCategory::AGENCY,
        );
        app()->instance(ActorContext::class, new ActorContext($identityId, Language::ENGLISH));
        app()->instance(AccountContext::class, $accountContext);

        $principal = Mockery::mock(Principal::class);
        $principal->shouldReceive('principalIdentifier')->once()->andReturn($wikiPrincipalId);
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
            ->once()->with($identityId, $accountId)->andReturn($principal);
        $accountResolver = app(AccountResolver::class);

        $middleware = new ResolveWikiContext(new PrincipalResolver($repository), $accountResolver);
        $middleware->handle(Request::create('/api/wiki/test', 'GET'), fn () => response('ok'));

        $this->assertSame($wikiPrincipalId, app(WikiContext::class)->principalIdentifier);
        $this->assertSame($accountContext, app(AccountContext::class));
    }
}
