<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

enum ConditionKey: string
{
    case RESOURCE_ACCOUNT_TYPE = 'resource:accountType';
    case RESOURCE_ACCOUNT_CATEGORY = 'resource:accountCategory';
    case AFFILIATION_REQUEST_PAIR_ALLOWED = 'affiliationRequest:pairAllowed';
    case RESOURCE_DELEGATION_ID = 'resource:delegationId';
    case RESOURCE_TARGET_ACCOUNT_ID = 'resource:targetAccountId';
}
