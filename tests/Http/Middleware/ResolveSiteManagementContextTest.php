<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Middleware\ResolveSiteManagementContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Mockery;
use RuntimeException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateAccountContext;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ResolveSiteManagementContextTest extends TestCase
{
    public function testCacheHitBindsContextWithoutRepositoryAccess(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $this->app()->instance(AccountContext::class, CreateAccountContext::create($identity, $accountIdentifier));
        $principalId = StrTestHelper::generateUuid();
        $this->app()->instance(ActorContext::class, new ActorContext($identity, Language::JAPANESE));
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldNotReceive('findByIdentityIdentifierAndAccountIdentifier');
        $this->app()->instance(PrincipalRepositoryInterface::class, $repository);
        Redis::shouldReceive('get')->once()->with('auth-context:site-management:' . $identity . ':' . $accountIdentifier)
            ->andReturn(json_encode(['principalIdentifier' => $principalId]));
        Redis::shouldReceive('setex')->never();

        $response = $this->app()->make(ResolveSiteManagementContext::class)->handle(
            Request::create('/api/site-management/contacts'),
            function () use ($principalId) {
                $this->assertSame($principalId, (string) $this->app()->make(SiteManagementContext::class)->principalIdentifier);

                return response('ok');
            },
        );
        $this->assertSame('ok', $response->getContent());
    }

    public function testCacheMissResolvesAndStoresContext(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $this->app()->instance(AccountContext::class, CreateAccountContext::create($identity, $accountIdentifier));
        $principalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $this->app()->instance(ActorContext::class, new ActorContext($identity, Language::JAPANESE));
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->with($identity, $accountIdentifier)->andReturn(new Principal($principalId, $identity, $accountIdentifier));
        $this->app()->instance(PrincipalRepositoryInterface::class, $repository);
        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();

        $this->app()->make(ResolveSiteManagementContext::class)->handle(
            Request::create('/api/site-management/contacts'),
            function () use ($principalId) {
                $this->assertSame($principalId, $this->app()->make(SiteManagementContext::class)->principalIdentifier);

                return response('ok');
            },
        );
    }

    public function testMissingPrincipalStillReturnsForbidden(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $this->app()->instance(AccountContext::class, CreateAccountContext::create($identity, $accountIdentifier));
        $this->app()->instance(ActorContext::class, new ActorContext($identity, Language::JAPANESE));
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->with($identity, $accountIdentifier)->andReturnNull();
        $this->app()->instance(PrincipalRepositoryInterface::class, $repository);
        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->never();
        $this->expectException(ForbiddenHttpException::class);

        $this->app()->make(ResolveSiteManagementContext::class)->handle(
            Request::create('/api/site-management/contacts'),
            fn () => throw new RuntimeException('Next handler must not be called'),
        );
    }
}
