<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

readonly class EmailSendingStatus
{
    public function __construct(
        public bool $sendingAllowed,
        public int $remainingSends,
        public ?int $retryAfterSeconds,
    ) {
    }
}
