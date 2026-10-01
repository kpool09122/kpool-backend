<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

enum StepUpAuthenticationScope: string
{
    case RECENT_AUTHENTICATION = 'recent_authentication';
}
