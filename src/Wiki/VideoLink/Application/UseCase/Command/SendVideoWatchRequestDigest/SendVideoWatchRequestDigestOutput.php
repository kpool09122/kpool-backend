<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest;

class SendVideoWatchRequestDigestOutput implements SendVideoWatchRequestDigestOutputPort
{
    public int $failedCount = 0;

    public function setFailedCount(int $failedCount): void
    {
        $this->failedCount = $failedCount;
    }
}
