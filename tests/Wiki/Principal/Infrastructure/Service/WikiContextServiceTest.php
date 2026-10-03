<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Infrastructure\Service\WikiContextService;
use Tests\TestCase;

class WikiContextServiceTest extends TestCase
{
    public function testForgetsImmediatelyOutsideTransaction(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $authContextCache = $this->createMock(AuthContextCache::class);
        $authContextCache->expects($this->once())->method('forgetWiki')->with($identityIdentifier);
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        (new WikiContextService($authContextCache))->forget($identityIdentifier);
    }

    public function testForgetsOnlyAfterCommit(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $authContextCache = $this->createMock(AuthContextCache::class);
        $authContextCache->expects($this->once())->method('forgetWiki')->with($identityIdentifier);
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        $callback = null;
        DB::shouldReceive('afterCommit')->once()->andReturnUsing(static function (callable $afterCommit) use (&$callback): void {
            $callback = $afterCommit;
        });
        (new WikiContextService($authContextCache))->forget($identityIdentifier);
        $this->assertIsCallable($callback);
        $callback();
    }

    #[Group('useDb')]
    public function testRealTransactionCommit(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $state = (object) ['called' => false];
        $authContextCache = $this->createMock(AuthContextCache::class);
        $authContextCache->expects($this->once())->method('forgetWiki')->with($identityIdentifier)->willReturnCallback(static function () use ($state): void {
            $state->called = true;
        });
        DB::commit();

        try {
            DB::beginTransaction();
            (new WikiContextService($authContextCache))->forget($identityIdentifier);
            $this->assertFalse($state->called);
            DB::commit();
            $this->assertTrue($state->called);
        } finally {
            DB::beginTransaction();
        }
    }

    #[Group('useDb')]
    public function testRealTransactionRollback(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $state = (object) ['called' => false];
        $authContextCache = $this->createMock(AuthContextCache::class);
        $authContextCache->expects($this->never())->method('forgetWiki');
        DB::commit();

        try {
            DB::beginTransaction();
            (new WikiContextService($authContextCache))->forget($identityIdentifier);
            $this->assertFalse($state->called);
            DB::rollBack();
            $this->assertFalse($state->called);
        } finally {
            DB::beginTransaction();
        }
    }
}
