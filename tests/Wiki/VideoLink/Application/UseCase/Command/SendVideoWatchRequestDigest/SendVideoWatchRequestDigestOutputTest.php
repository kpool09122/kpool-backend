<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest;

use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestOutput;

class SendVideoWatchRequestDigestOutputTest extends TestCase
{
    public function testReportsTheLatestFailureCount(): void
    {
        $output = new SendVideoWatchRequestDigestOutput();
        self::assertSame(0, $output->failedCount);
        $output->setFailedCount(2);
        self::assertSame(2, $output->failedCount);
        $output->setFailedCount(0);
        self::assertSame(0, $output->failedCount);
    }
}
