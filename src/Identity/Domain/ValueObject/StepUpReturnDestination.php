<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

enum StepUpReturnDestination: string
{
    case PASSKEYS = 'passkeys';
    case WITHDRAWAL = 'withdrawal';

}
