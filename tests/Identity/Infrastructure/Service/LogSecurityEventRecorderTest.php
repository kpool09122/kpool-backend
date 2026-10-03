<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Identity\Infrastructure\Service\LogSecurityEventRecorder;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class LogSecurityEventRecorderTest extends TestCase
{
    public function testRecordsIdentityAndAdditionalContextImmediately(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('notice')->with('Identity security event.', ['security_event' => 'passkey_added', 'identity_id' => (string) $identityIdentifier, 'method' => 'passkey']);
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        (new LogSecurityEventRecorder($logger))->record('passkey_added', $identityIdentifier, ['method' => 'passkey']);
    }

    public function testDefersRecordingUntilCommit(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $state = (object) ['called' => false];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('notice')->willReturnCallback(static function () use ($state): void {
            $state->called = true;
        });
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        $callback = null;
        DB::shouldReceive('afterCommit')->once()->andReturnUsing(static function (callable $afterCommit) use (&$callback): void {
            $callback = $afterCommit;
        });
        (new LogSecurityEventRecorder($logger))->record('passkey_added', $identityIdentifier);
        $this->assertFalse($state->called);
        $this->assertIsCallable($callback);
        $callback();
        $this->assertTrue($state->called);
    }
}
