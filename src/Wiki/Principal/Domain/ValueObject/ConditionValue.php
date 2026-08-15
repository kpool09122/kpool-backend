<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

enum ConditionValue: string
{
    case PRINCIPAL_AGENCY_WIKI_IDENTIFIERS = '${principal.agencyWikiIdentifiers}';
    case PRINCIPAL_GROUP_WIKI_IDENTIFIERS = '${principal.groupWikiIdentifiers}';
    case PRINCIPAL_TALENT_GROUP_WIKI_IDENTIFIERS = '${principal.talentGroupWikiIdentifiers}';
    case PRINCIPAL_TALENT_WIKI_IDENTIFIERS = '${principal.talentWikiIdentifiers}';
    case PRINCIPAL_AFFILIATED_TALENT_WIKI_IDENTIFIERS = '${principal.affiliatedTalentWikiIdentifiers}';
    case PRINCIPAL_ID = '${principal.id}';
}
