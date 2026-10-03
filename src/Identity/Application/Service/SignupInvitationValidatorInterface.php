<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\OneTimeToken;

interface SignupInvitationValidatorInterface
{
    public function validate(OneTimeToken $token, Email $email): void;
}
