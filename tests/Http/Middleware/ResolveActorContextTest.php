<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Context\ActorContext;
use Application\Http\Middleware\ResolveActorContext;
use Application\Models\Identity\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ResolveActorContextTest extends TestCase
{
    public function testBindsActorContextToContainerAndSetsLocale(): void
    {
        $identityId = StrTestHelper::generateUuid();
        $delegationId = StrTestHelper::generateUuid();
        $originalIdentityId = StrTestHelper::generateUuid();

        $identity = new Identity();
        $identity->id = $identityId;
        $identity->language = 'ja';
        $identity->delegation_identifier = $delegationId;
        $identity->original_identity_identifier = $originalIdentityId;

        Auth::shouldReceive('user')->once()->andReturn($identity);

        $request = Request::create('/api/test', 'GET');
        $middleware = new ResolveActorContext();

        $middleware->handle($request, function () {
            return response('ok');
        });

        $this->assertTrue(app()->bound(ActorContext::class));

        /** @var ActorContext $actorContext */
        $actorContext = app(ActorContext::class);

        $this->assertSame($identityId, (string) $actorContext->identityIdentifier);
        $this->assertSame(Language::JAPANESE, $actorContext->language);
        $this->assertSame($delegationId, (string) $actorContext->delegationIdentifier);
        $this->assertSame($originalIdentityId, (string) $actorContext->originalIdentityIdentifier);
        $this->assertSame('ja', app()->getLocale());
    }

    public function testBindsActorContextWithNullDelegation(): void
    {
        $identityId = StrTestHelper::generateUuid();

        $identity = new Identity();
        $identity->id = $identityId;
        $identity->language = 'en';
        $identity->delegation_identifier = null;
        $identity->original_identity_identifier = null;

        Auth::shouldReceive('user')->once()->andReturn($identity);

        $request = Request::create('/api/test', 'GET');
        $middleware = new ResolveActorContext();

        $middleware->handle($request, function () {
            return response('ok');
        });

        /** @var ActorContext $actorContext */
        $actorContext = app(ActorContext::class);

        $this->assertSame($identityId, (string) $actorContext->identityIdentifier);
        $this->assertSame(Language::ENGLISH, $actorContext->language);
        $this->assertNull($actorContext->delegationIdentifier);
        $this->assertNull($actorContext->originalIdentityIdentifier);
        $this->assertSame('en', app()->getLocale());
    }
}
