<?php

declare(strict_types=1);

namespace Application\Jobs\Wiki;

use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestOutput;

class SendVideoWatchRequestDigestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public ?Email $recipientEmail = null;

    public function __construct(?Email $recipientEmail = null)
    {
        $this->recipientEmail = $recipientEmail;
    }

    /** @throws RuntimeException */
    public function handle(SendVideoWatchRequestDigestInterface $useCase): void
    {
        $output = new SendVideoWatchRequestDigestOutput();
        $useCase->process(new SendVideoWatchRequestDigestInput(new DateTimeImmutable(), $this->recipientEmail), $output);
        if ($output->failedCount > 0) {
            throw new RuntimeException('Video watch request digest delivery failed.');
        }
    }
}
