<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Context\ActorContext;
use Application\Http\Context\PrincipalResolver;
use Application\Http\Context\WikiContext;
use Application\Http\Middleware\ResolveWikiContext;
use Illuminate\Http\Request;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ResolveWikiContextTest extends TestCase
{
    public function testBindsWikiContextToContainer(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        app()->instance(ActorContext::class, new ActorContext($identityIdentifier, Language::ENGLISH, null, null));

        /** @var Principal&Mockery\MockInterface $principal */
        $principal = Mockery::mock(Principal::class);
        $principal->shouldReceive('principalIdentifier')->once()->andReturn($principalIdentifier);

        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifier')
            ->once()
            ->with($identityIdentifier)
            ->andReturn($principal);

        $principalResolver = new PrincipalResolver($repository);

        $middleware = new ResolveWikiContext($principalResolver);
        $request = Request::create('/api/wiki/test', 'GET');

        $middleware->handle($request, function () {
            return response('ok');
        });

        $this->assertTrue(app()->bound(WikiContext::class));

        /** @var WikiContext $wikiContext */
        $wikiContext = app(WikiContext::class);
        $this->assertSame($principalIdentifier, $wikiContext->principalIdentifier);
    }
}
