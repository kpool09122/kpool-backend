<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail;

use Source\Identity\Domain\ValueObject\AuthCode;

readonly class VerifySocialLinkingEmailInput implements VerifySocialLinkingEmailInputPort
{
    public function __construct(private AuthCode $code)
    {
    }

    public function code(): AuthCode
    {
        return $this->code;
    }
}
