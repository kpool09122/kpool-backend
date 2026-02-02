<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

enum EnglishLevel: string
{
    case NATIVE = 'native';
    case FLUENT = 'fluent';
    case CONVERSATIONAL = 'conversational';
    case BASIC = 'basic';
    case NONE = 'none';
}
