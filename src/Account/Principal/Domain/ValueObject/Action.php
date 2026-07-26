<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

enum Action: string
{
    case INVITE_MEMBER = 'account:member:invite';
    case UPDATE = 'account:update';
    case SETTINGS_UPDATE = 'account:settings:update';
    case DELETE = 'account:delete';
    case BILLING_MANAGE = 'account:billing:manage';
    case DELEGATION_MANAGE = 'account:delegation:manage';
    case PRINCIPAL_GROUP_MANAGE = 'account:principal-group:manage';
}
