<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\ValueObject;

enum ResourceType: string
{
    case PRINCIPAL_GROUP = 'principal_group';
    case PAYMENT_METHOD = 'payment_method';
}
