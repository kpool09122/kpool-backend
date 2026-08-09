<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

enum ConditionKey: string
{
    case RESOURCE_ACCOUNT_TYPE = 'resource:accountType';
}
