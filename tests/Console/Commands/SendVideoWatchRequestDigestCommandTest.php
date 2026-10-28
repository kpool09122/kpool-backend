<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Application\Console\Commands\SendVideoWatchRequestDigestCommand;
use Application\Jobs\Wiki\SendVideoWatchRequestDigestJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestInputPort;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestOutputPort;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class SendVideoWatchRequestDigestCommandTest extends TestCase
{
    public function testCommandQueuesDigestAndReportsEnqueueOnly(): void
    {
        Queue::fake();
        Artisan::registerCommand($this->app()->make(SendVideoWatchRequestDigestCommand::class));
        $output = new BufferedOutput();
        self::assertSame(0, Artisan::call('video-watch-requests:send-digest', [], $output));
        Queue::assertPushed(SendVideoWatchRequestDigestJob::class, static fn (SendVideoWatchRequestDigestJob $job): bool => $job->recipientEmail === null && $job->tries === 1);
        self::assertStringContainsString('enqueued', $output->fetch());
    }

    public function testCommandQueuesOnlyTheSpecifiedRecipient(): void
    {
        Queue::fake();
        Artisan::registerCommand($this->app()->make(SendVideoWatchRequestDigestCommand::class));
        self::assertSame(0, Artisan::call('video-watch-requests:send-digest', ['--email' => 'owner@example.com'], new BufferedOutput()));
        Queue::assertPushed(SendVideoWatchRequestDigestJob::class, static fn (SendVideoWatchRequestDigestJob $job): bool => (string) $job->recipientEmail === 'owner@example.com' && $job->tries === 1);
        Queue::assertPushed(SendVideoWatchRequestDigestJob::class, 1);
    }

    /** @return iterable<string, array{?string}> */
    public static function invalidEmails(): iterable
    {
        yield 'invalid format' => ['not-an-email'];
        yield 'empty option' => [''];
        yield 'missing option value' => [null];
    }

    #[DataProvider('invalidEmails')]
    public function testInvalidRecipientIsRejectedWithoutQueuing(?string $email): void
    {
        Queue::fake();
        Artisan::registerCommand($this->app()->make(SendVideoWatchRequestDigestCommand::class));
        $output = new BufferedOutput();
        self::assertSame(1, Artisan::call('video-watch-requests:send-digest', ['--email' => $email], $output));
        self::assertStringContainsString('Invalid recipient email address.', $output->fetch());
        Queue::assertNothingPushed();
    }

    public function testQueuedJobPreservesTargetEmailAndPassesItToUseCase(): void
    {
        $email = new Email('owner@example.com');
        $job = unserialize(serialize(new SendVideoWatchRequestDigestJob($email)));
        self::assertInstanceOf(SendVideoWatchRequestDigestJob::class, $job);
        /** @var SendVideoWatchRequestDigestInterface&MockInterface $useCase */
        $useCase = Mockery::mock(SendVideoWatchRequestDigestInterface::class);
        $useCase->shouldReceive('process')->once()->andReturnUsing(static function (SendVideoWatchRequestDigestInputPort $input, SendVideoWatchRequestDigestOutputPort $output): void {
            self::assertSame('owner@example.com', (string) $input->recipientEmail());
            $output->setFailedCount(0);
        });
        $job->handle($useCase);
    }

    public function testEnqueueFailureReturnsNonzero(): void
    {
        Queue::shouldReceive('connection')->andThrow(new RuntimeException('queue unavailable'));
        Artisan::registerCommand($this->app()->make(SendVideoWatchRequestDigestCommand::class));
        self::assertSame(1, Artisan::call('video-watch-requests:send-digest', [], new BufferedOutput()));
    }

    public function testJobReportsFailureWithoutAutomaticRetry(): void
    {
        /** @var SendVideoWatchRequestDigestInterface&MockInterface $useCase */
        $useCase = Mockery::mock(SendVideoWatchRequestDigestInterface::class);
        $useCase->shouldReceive('process')->once()->andReturnUsing(static function (SendVideoWatchRequestDigestInputPort $input, SendVideoWatchRequestDigestOutputPort $output): void {
            $output->setFailedCount(1);
        });
        $job = new SendVideoWatchRequestDigestJob();
        self::assertSame(1, $job->tries);
        $this->expectException(RuntimeException::class);
        $job->handle($useCase);
    }

    public function testJobSuccess(): void
    {
        /** @var SendVideoWatchRequestDigestInterface&MockInterface $useCase */
        $useCase = Mockery::mock(SendVideoWatchRequestDigestInterface::class);
        $useCase->shouldReceive('process')->once()->andReturnUsing(static function (SendVideoWatchRequestDigestInputPort $input, SendVideoWatchRequestDigestOutputPort $output): void {
            $output->setFailedCount(0);
        });
        (new SendVideoWatchRequestDigestJob())->handle($useCase);
    }
}
