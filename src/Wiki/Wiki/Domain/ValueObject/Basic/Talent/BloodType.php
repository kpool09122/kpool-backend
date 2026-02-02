<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

enum BloodType: string
{
    case A = 'A';
    case B = 'B';
    case O = 'O';
    case AB = 'AB';
}
