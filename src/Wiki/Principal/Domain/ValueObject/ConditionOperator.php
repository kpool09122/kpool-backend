<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

enum ConditionOperator: string
{
    case EQUALS = 'eq';
    case NOT_EQUALS = 'ne';
    case IN = 'in';
    case NOT_IN = 'not_in';
}
