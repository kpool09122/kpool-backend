<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

enum StepUpAuthenticationScope: string
{
    case PASSKEY_MANAGE = 'passkey.manage';
}
