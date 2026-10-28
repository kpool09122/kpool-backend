<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest;

interface SendVideoWatchRequestDigestOutputPort
{
    public function setFailedCount(int $failedCount): void;
}
