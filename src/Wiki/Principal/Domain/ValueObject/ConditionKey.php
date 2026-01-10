<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

enum ConditionKey: string
{
    case RESOURCE_IS_OFFICIAL = 'resource:isOfficial';
    case RESOURCE_AGENCY_ID = 'resource:agencyId';
    case RESOURCE_GROUP_ID = 'resource:groupId';
    case RESOURCE_TALENT_ID = 'resource:talentId';
}
