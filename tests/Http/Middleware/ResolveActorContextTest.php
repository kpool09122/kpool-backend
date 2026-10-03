<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Context\ActorContext;
use Application\Http\Middleware\ResolveActorContext;
use Application\Models\Identity\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ResolveActorContextTest extends TestCase
{
    public function testBindsActorContextToContainerAndSetsLocale(): void
    {
        $identityId = StrTestHelper::generateUuid();
        $identity = new Identity();
        $identity->id = $identityId;
        $identity->language = 'ja';

        Auth::shouldReceive('user')->once()->andReturn($identity);

        $request = Request::create('/api/test', 'GET');
        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();

        $middleware = app(ResolveActorContext::class);

        $middleware->handle($request, function () {
            return response('ok');
        });

        $this->assertTrue(app()->bound(ActorContext::class));

        /** @var ActorContext $actorContext */
        $actorContext = app(ActorContext::class);

        $this->assertSame($identityId, (string) $actorContext->identityIdentifier);
        $this->assertSame(Language::JAPANESE, $actorContext->language);
        $this->assertSame('ja', app()->getLocale());
    }
}
