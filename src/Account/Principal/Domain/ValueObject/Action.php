<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

enum Action: string
{
    case READ = 'account:read';
    case INVITE_MEMBER = 'account:member:invite';
    case UPDATE = 'account:update';
    case PRINCIPAL_GROUP_MANAGE = 'account:principal-group:manage';
    case ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE = 'account:category-change-request:manage';
    case AFFILIATION_REQUEST_CREATE = 'account:affiliation-request:create';
    case AFFILIATION_REQUEST_RECEIVE = 'account:affiliation-request:receive';
    case AFFILIATION_APPROVE = 'account:affiliation:approve';
    case AFFILIATION_REJECT = 'account:affiliation:reject';
}
