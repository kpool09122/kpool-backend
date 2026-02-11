<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

enum ListType: string
{
    case BULLET = 'bullet';
    case NUMBERED = 'numbered';
}
