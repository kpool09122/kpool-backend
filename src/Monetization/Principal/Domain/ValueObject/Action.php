<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\ValueObject;

enum Action: string
{
    case PRINCIPAL_GROUP_READ = 'principal_group_read';
    case PRINCIPAL_GROUP_MANAGE = 'principal_group_manage';
    case CARD_REGISTRATION_PREPARE = 'card_registration_prepare';
    case PAYMENT_METHOD_READ = 'payment_method_read';
    case PAYMENT_METHOD_DELETE = 'payment_method_delete';
    case PAYMENT_METHOD_REGISTER = 'payment_method_register';
}
