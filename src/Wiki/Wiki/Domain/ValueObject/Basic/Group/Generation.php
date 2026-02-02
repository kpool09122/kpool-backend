<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Group;

enum Generation: string
{
    case FIRST = '1st';
    case SECOND = '2nd';
    case THIRD = '3rd';
    case FOURTH = '4th';
    case FIFTH = '5th';
}
