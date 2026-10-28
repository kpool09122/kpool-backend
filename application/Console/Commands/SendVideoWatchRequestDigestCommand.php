<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Application\Jobs\Wiki\SendVideoWatchRequestDigestJob;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Override;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Email;
use Throwable;

class SendVideoWatchRequestDigestCommand extends Command
{
    #[Override]
    protected $signature = 'video-watch-requests:send-digest {--email= : 指定した通知先メールアドレスだけを対象にする}';

    #[Override]
    protected $description = 'これみてリクエストの日次集約通知をキューに投入する';

    public function handle(LoggerInterface $logger): int
    {
        $emailOption = $this->option('email');
        if (($emailOption === null && $this->input->hasParameterOption('--email')) || ($emailOption !== null && ! is_string($emailOption))) {
            $this->error('Invalid recipient email address.');

            return self::FAILURE;
        }

        try {
            $recipientEmail = $emailOption === null ? null : new Email(trim($emailOption));
        } catch (InvalidArgumentException) {
            $this->error('Invalid recipient email address.');

            return self::FAILURE;
        }

        try {
            SendVideoWatchRequestDigestJob::dispatch($recipientEmail);
        } catch (Throwable $exception) {
            $logger->error('Video watch request digest enqueue failed', ['exception_type' => $exception::class]);
            $this->error('Digest enqueue failed. See application logs.');

            return self::FAILURE;
        }
        $this->info('Video watch request digest enqueued; delivery is not complete yet.');

        return self::SUCCESS;
    }
}
