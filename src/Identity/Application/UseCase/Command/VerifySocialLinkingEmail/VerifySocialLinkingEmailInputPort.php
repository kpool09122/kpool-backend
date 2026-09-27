<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail;

use Source\Identity\Domain\ValueObject\AuthCode;

interface VerifySocialLinkingEmailInputPort
{
    public function code(): AuthCode;
}
