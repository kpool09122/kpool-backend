<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Shared\Domain\ValueObject\Email;

interface AuthCodeSendingRateLimitServiceInterface
{
    public function reserve(Email $email): EmailSendingStatus;
}
