<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Action\Identity\Command\WithdrawIdentity\WithdrawIdentityAction;
use Application\Http\Context\ActorContext;
use Application\Http\Middleware\StartApplicationSession;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use RuntimeException;
use SessionHandlerInterface;
use Source\Identity\Application\UseCase\Command\WithdrawIdentity\WithdrawIdentityInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class StartApplicationSessionTest extends TestCase
{
    #[Group('useDb')]
    public function testCommittedWithdrawalStillReturns204WhenOuterSessionSaveFails(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $failure = new RuntimeException('Session storage write failed');
        $middleware = $this->middleware($failure);
        $request = Request::create('/api/identity/identities/me', 'DELETE');
        /** @var WithdrawIdentityInterface&MockInterface $withdraw */
        $withdraw = Mockery::mock(WithdrawIdentityInterface::class);
        $withdraw->shouldReceive('process')->once()->with($identityIdentifier)->andReturnUsing(static function () use ($identityIdentifier): void {
            DB::table('identities')->where('id', (string) $identityIdentifier)->delete();
        });
        /** @var AuthServiceInterface&MockInterface $auth */
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('invalidateAllSessions')->once()->with($identityIdentifier);
        $auth->shouldReceive('logout')->once();
        $action = new WithdrawIdentityAction($withdraw, new ActorContext($identityIdentifier, Language::ENGLISH), $auth, new NullLogger(), $request);
        /** @var MockInterface $log */
        $log = Log::spy();

        try {
            DB::commit();
            $this->assertSame(0, DB::transactionLevel());
            $response = (new Pipeline($this->app()))->send($request)->through([$middleware])->then(static fn (): Response => $action());
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame(204, $response->getStatusCode());
            $this->assertTrue($request->attributes->get(WithdrawIdentityAction::COMMITTED_ATTRIBUTE));
            $this->assertDatabaseMissing('identities', ['id' => (string) $identityIdentifier]);
            $log->shouldHaveReceived('error')->once()->with('Failed to save session after committed identity withdrawal.', ['exception' => $failure]);
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::table('identities')->where('id', (string) $identityIdentifier)->delete();
            DB::beginTransaction();
        }
    }

    public function testOrdinaryRouteSessionSaveFailureStillThrows(): void
    {
        $failure = new RuntimeException('Ordinary route session storage failure');
        $request = Request::create('/api/identity/auth/csrf-token', 'GET', server: ['HTTP_X_IDENTITY_WITHDRAWAL_COMMITTED' => 'true']);

        try {
            (new Pipeline($this->app()))->send($request)->through([$this->middleware($failure)])->then(static fn (): Response => response()->noContent());
            $this->fail('An ordinary session-save failure must propagate');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
    }

    private function middleware(RuntimeException $failure): StartApplicationSession
    {
        $this->app()['config']->set('session.driver', 'failing-write');
        $this->app()['config']->set('session.lottery', [0, 100]);
        $handler = Mockery::mock(SessionHandlerInterface::class);
        $handler->shouldReceive('read')->andReturn('');
        $handler->shouldReceive('write')->once()->andThrow($failure);
        $manager = $this->app()->make(SessionManager::class);
        $manager->extend('failing-write', fn () => $handler);

        return new StartApplicationSession($manager);
    }
}
