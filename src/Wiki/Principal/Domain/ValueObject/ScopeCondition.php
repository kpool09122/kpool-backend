<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

enum ScopeCondition: string
{
    case NONE = 'none';
    case OWN_AGENCY = 'own_agency';
    case OWN_GROUPS = 'own_groups';
    case OWN_TALENTS = 'own_talents';
    case OWN_GROUPS_OR_TALENTS = 'own_groups_or_talents';
}
