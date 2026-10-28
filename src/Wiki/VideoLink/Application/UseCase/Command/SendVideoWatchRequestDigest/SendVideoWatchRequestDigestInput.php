<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Email;

readonly class SendVideoWatchRequestDigestInput implements SendVideoWatchRequestDigestInputPort
{
    public function __construct(private DateTimeImmutable $executedAt, private ?Email $recipientEmail = null)
    {
    }

    public function executedAt(): DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function recipientEmail(): ?Email
    {
        return $this->recipientEmail;
    }
}
