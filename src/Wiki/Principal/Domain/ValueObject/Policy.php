<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

enum Policy: string
{
    case FULL_ACCESS = 'full_access';
    case BASIC_EDITING = 'basic_editing';
    case AGENCY_MANAGEMENT = 'agency_management';
    case GROUP_MANAGEMENT = 'group_management';
    case TALENT_MANAGEMENT = 'talent_management';
    case DENY_AGENCY_APPROVAL = 'deny_agency_approval';
    case DENY_ROLLBACK = 'deny_rollback';

    /**
     * @return Statement[]
     */
    public function statements(): array
    {
        return match ($this) {
            self::FULL_ACCESS => [
                new Statement(
                    effect: Effect::ALLOW,
                    actions: Action::cases(),
                    resourceTypes: ResourceType::cases(),
                    scopeCondition: ScopeCondition::NONE,
                ),
            ],
            self::BASIC_EDITING => [
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::CREATE, Action::EDIT, Action::SUBMIT],
                    resourceTypes: ResourceType::cases(),
                    scopeCondition: ScopeCondition::NONE,
                ),
            ],
            self::AGENCY_MANAGEMENT => [
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH],
                    resourceTypes: ResourceType::cases(),
                    scopeCondition: ScopeCondition::OWN_AGENCY,
                ),
            ],
            self::GROUP_MANAGEMENT => [
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH],
                    resourceTypes: [ResourceType::GROUP, ResourceType::TALENT, ResourceType::SONG],
                    scopeCondition: ScopeCondition::OWN_GROUPS,
                ),
            ],
            self::TALENT_MANAGEMENT => [
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH],
                    resourceTypes: [ResourceType::GROUP],
                    scopeCondition: ScopeCondition::OWN_GROUPS,
                ),
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH],
                    resourceTypes: [ResourceType::TALENT],
                    scopeCondition: ScopeCondition::OWN_TALENTS,
                ),
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH],
                    resourceTypes: [ResourceType::SONG],
                    scopeCondition: ScopeCondition::OWN_GROUPS_OR_TALENTS,
                ),
            ],
            self::DENY_AGENCY_APPROVAL => [
                new Statement(
                    effect: Effect::DENY,
                    actions: [Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH],
                    resourceTypes: [ResourceType::AGENCY],
                    scopeCondition: ScopeCondition::NONE,
                ),
            ],
            self::DENY_ROLLBACK => [
                new Statement(
                    effect: Effect::DENY,
                    actions: [Action::ROLLBACK],
                    resourceTypes: ResourceType::cases(),
                    scopeCondition: ScopeCondition::NONE,
                ),
            ],
        };
    }
}
