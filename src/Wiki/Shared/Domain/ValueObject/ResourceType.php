<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

enum ResourceType: string
{
    case AGENCY = 'agency';
    case GROUP = 'group';
    case TALENT = 'talent';
    case SONG = 'song';
}
