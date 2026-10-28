<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Email;

interface SendVideoWatchRequestDigestInputPort
{
    public function executedAt(): DateTimeImmutable;

    public function recipientEmail(): ?Email;
}
