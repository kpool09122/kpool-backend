<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Action\Identity\Command\WithdrawFromService\WithdrawFromServiceAction;
use Application\Http\Action\Identity\Command\WithdrawFromService\WithdrawFromServiceRequest;
use Application\Http\Context\ActorContext;
use Application\Http\Context\ServiceWithdrawalContext;
use Application\Http\Middleware\StartApplicationSession;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use SessionHandlerInterface;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInputPort;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutputPort;
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
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->with('Failed to save session after committed identity withdrawal.', ['exception' => $failure]);
        $middleware = $this->middleware($failure, $logger);
        $request = Request::create('/api/identity/identities/me', 'DELETE');
        /** @var WithdrawFromServiceInterface&MockInterface $withdraw */
        $withdraw = Mockery::mock(WithdrawFromServiceInterface::class);
        $withdraw->shouldReceive('process')->once()->with(Mockery::on(static fn (WithdrawFromServiceInputPort $input): bool => $input->identityIdentifier() === $identityIdentifier), Mockery::type(WithdrawFromServiceOutputPort::class))->andReturnUsing(static function () use ($identityIdentifier): void {
            DB::table('identities')->where('id', (string) $identityIdentifier)->delete();
        });
        $action = new WithdrawFromServiceAction($withdraw, new ActorContext($identityIdentifier, Language::ENGLISH), new NullLogger(), $request);

        try {
            DB::commit();
            $this->assertSame(0, DB::transactionLevel());
            $response = (new Pipeline($this->app()))->send($request)->through([$middleware])->then(static fn (): Response => $action(WithdrawFromServiceRequest::create('/', 'DELETE', ['confirmationIdentityName' => 'test-identity'])));
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame(204, $response->getStatusCode());
            $this->assertTrue(ServiceWithdrawalContext::isCommitted($request));
            $this->assertDatabaseMissing('identities', ['id' => (string) $identityIdentifier]);
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
            (new Pipeline($this->app()))->send($request)->through([$this->middleware($failure, new NullLogger())])->then(static fn (): Response => response()->noContent());
            $this->fail('An ordinary session-save failure must propagate');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
    }

    private function middleware(RuntimeException $failure, LoggerInterface $logger): StartApplicationSession
    {
        $this->app()['config']->set('session.driver', 'failing-write');
        $this->app()['config']->set('session.lottery', [0, 100]);
        $handler = Mockery::mock(SessionHandlerInterface::class);
        $handler->shouldReceive('read')->andReturn('');
        $handler->shouldReceive('write')->once()->andThrow($failure);
        $manager = $this->app()->make(SessionManager::class);
        $manager->extend('failing-write', fn () => $handler);

        return new StartApplicationSession($manager, $logger);
    }
}
