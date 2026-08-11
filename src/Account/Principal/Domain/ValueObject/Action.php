<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

enum Action: string
{
    case READ = 'account:read';
    case INVITE_MEMBER = 'account:member:invite';
    case UPDATE = 'account:update';
    case PRINCIPAL_GROUP_MANAGE = 'account:principal-group:manage';
    case ACCOUNT_TYPE_CHANGE_REQUEST_APPROVE = 'account:type-change-request:approve';
}
