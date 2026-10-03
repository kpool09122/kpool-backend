<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Mail\PasskeyRecoveryCompletedMail;
use Closure;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Identity\Infrastructure\Service\PasskeyRecoveryNotificationService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class PasskeyRecoveryNotificationServiceTest extends TestCase
{
    #[DataProvider('activeTransactionLevels')]
    public function testNotificationUsesDefaultConnectionOnlyAfterCommit(int $transactionLevel): void
    {
        config(['queue.default' => 'sync']);
        Mail::fake();
        DB::shouldReceive('transactionLevel')->once()->andReturn($transactionLevel);
        $afterCommit = null;
        DB::shouldReceive('afterCommit')->once()->andReturnUsing(static function (Closure $callback) use (&$afterCommit): void {
            $afterCommit = $callback;
        });

        (new PasskeyRecoveryNotificationService(new NullLogger()))->notifyCompleted(new Email('user@example.com'), Language::JAPANESE);
        Mail::assertNothingOutgoing();
        $this->assertInstanceOf(Closure::class, $afterCommit);
        $afterCommit();

        Mail::assertQueued(PasskeyRecoveryCompletedMail::class, static function (PasskeyRecoveryCompletedMail $mail): bool {
            return $mail->hasTo('user@example.com') && $mail->connection === null && $mail->queue === null && $mail->language === Language::JAPANESE;
        });
        Mail::assertNothingSent();
    }

    public function testNotificationIsQueuedImmediatelyWithoutTransaction(): void
    {
        config(['queue.default' => 'sync']);
        Mail::fake();
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        DB::shouldReceive('afterCommit')->never();

        (new PasskeyRecoveryNotificationService(new NullLogger()))->notifyCompleted(new Email('user@example.com'), Language::JAPANESE);

        Mail::assertQueued(PasskeyRecoveryCompletedMail::class, static function (PasskeyRecoveryCompletedMail $mail): bool {
            return $mail->hasTo('user@example.com') && $mail->connection === null && $mail->queue === null && $mail->language === Language::JAPANESE;
        });
        Mail::assertNothingSent();
    }

    #[DataProvider('allTransactionLevels')]
    public function testQueueFailureIsLoggedWithoutEscaping(int $transactionLevel): void
    {
        $failure = new RuntimeException('Queue unavailable');
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->with('Failed to queue passkey recovery completion email.', ['exception' => $failure]);
        $pending = Mockery::mock(PendingMail::class);
        Mail::shouldReceive('to')->once()->with('user@example.com')->andReturn($pending);
        $pending->shouldReceive('queue')->once()->andThrow($failure);
        DB::shouldReceive('transactionLevel')->once()->andReturn($transactionLevel);
        if ($transactionLevel > 0) {
            DB::shouldReceive('afterCommit')->once()->andReturnUsing(static fn (Closure $callback) => $callback());
        } else {
            DB::shouldReceive('afterCommit')->never();
        }

        (new PasskeyRecoveryNotificationService($logger))->notifyCompleted(new Email('user@example.com'), Language::JAPANESE);
    }

    /** @return array<string, array{int}> */
    public static function activeTransactionLevels(): array
    {
        return ['transaction' => [1], 'nested transaction' => [2]];
    }

    /** @return array<string, array{int}> */
    public static function allTransactionLevels(): array
    {
        return ['no transaction' => [0], ...self::activeTransactionLevels()];
    }
}
