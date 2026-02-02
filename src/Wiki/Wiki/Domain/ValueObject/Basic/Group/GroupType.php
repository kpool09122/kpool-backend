<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Group;

enum GroupType: string
{
    case BOY_GROUP = 'boy_group';
    case GIRL_GROUP = 'girl_group';
    case CO_ED = 'co_ed';
}
