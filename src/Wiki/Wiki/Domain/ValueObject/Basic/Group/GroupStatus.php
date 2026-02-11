<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Group;

enum GroupStatus: string
{
    case ACTIVE = 'active';
    case DISBANDED = 'disbanded';
    case HIATUS = 'hiatus';
}
