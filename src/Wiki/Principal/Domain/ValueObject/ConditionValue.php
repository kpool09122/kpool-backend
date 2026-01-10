<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

enum ConditionValue: string
{
    case PRINCIPAL_AGENCY_ID = '${principal.agencyId}';
    case PRINCIPAL_WIKI_GROUP_IDS = '${principal.wikiGroupIds}';
    case PRINCIPAL_TALENT_IDS = '${principal.talentIds}';
}
