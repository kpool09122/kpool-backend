<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest;

interface SendVideoWatchRequestDigestInterface
{
    public function process(SendVideoWatchRequestDigestInputPort $input, SendVideoWatchRequestDigestOutputPort $output): void;
}
