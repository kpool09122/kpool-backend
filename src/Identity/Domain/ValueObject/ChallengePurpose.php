<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

enum ChallengePurpose: string
{
    case REGISTRATION = 'registration';
    case AUTHENTICATION = 'authentication';
    case ADDITION = 'addition';
}
