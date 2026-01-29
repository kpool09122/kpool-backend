<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Language;

final readonly class AutomaticDraftTalentCreationPayload
{
    /**
     * @param GroupIdentifier[] $groupIdentifiers
     */
    public function __construct(
        private Language                   $language,
        private TalentName                 $name,
        private ?AgencyIdentifier          $agencyIdentifier,
        private array                      $groupIdentifiers,
    ) {
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): TalentName
    {
        return $this->name;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    /**
     * @return GroupIdentifier[]
     */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }
}
