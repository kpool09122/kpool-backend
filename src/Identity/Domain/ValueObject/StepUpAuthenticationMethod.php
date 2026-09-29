<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

enum StepUpAuthenticationMethod: string
{
    case PASSKEY = 'passkey';
    case SSO = 'sso';
}
