<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

enum Role: string
{
    case AGENCY_ACTOR = 'agency_actor';
    case GROUP_ACTOR = 'group_actor';
    case TALENT_ACTOR = 'talent_actor';
    case SENIOR_COLLABORATOR = 'senior_collaborator';
    case COLLABORATOR = 'collaborator';
    case ADMINISTRATOR = 'administrator';
    case NONE = 'none';

    /**
     * @return Policy[]
     */
    public function policies(): array
    {
        return match ($this) {
            self::ADMINISTRATOR => [Policy::FULL_ACCESS],
            self::SENIOR_COLLABORATOR => [Policy::FULL_ACCESS],
            self::AGENCY_ACTOR => [
                Policy::BASIC_EDITING,
                Policy::AGENCY_MANAGEMENT,
            ],
            self::GROUP_ACTOR => [
                Policy::BASIC_EDITING,
                Policy::GROUP_MANAGEMENT,
                Policy::DENY_AGENCY_APPROVAL,
            ],
            self::TALENT_ACTOR => [
                Policy::BASIC_EDITING,
                Policy::TALENT_MANAGEMENT,
                Policy::DENY_AGENCY_APPROVAL,
            ],
            self::COLLABORATOR => [Policy::BASIC_EDITING],
            self::NONE => [],
        };
    }
}
