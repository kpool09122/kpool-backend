<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

enum Action: string
{
    case INVITATION_CREATE = 'account:invitation:create';
    case UPDATE_NAME = 'account:updateName';
    case SETTINGS_UPDATE = 'account:settings:update';
    case DELETE = 'account:delete';
    case BILLING_MANAGE = 'account:billing:manage';
    case DELEGATION_MANAGE = 'account:delegation:manage';
}
