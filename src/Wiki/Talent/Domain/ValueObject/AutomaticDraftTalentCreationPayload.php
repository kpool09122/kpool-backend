<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

final readonly class AutomaticDraftTalentCreationPayload
{
    /**
     * @param GroupIdentifier[] $groupIdentifiers
     */
    public function __construct(
        private EditorIdentifier $editorIdentifier,
        private Translation $translation,
        private TalentName $name,
        private RealName $realName,
        private ?AgencyIdentifier $agencyIdentifier,
        private array $groupIdentifiers,
        private ?Birthday $birthday,
        private Career $career,
        private AutomaticDraftTalentSource $source,
    ) {
    }

    public function editorIdentifier(): EditorIdentifier
    {
        return $this->editorIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }

    public function name(): TalentName
    {
        return $this->name;
    }

    public function realName(): RealName
    {
        return $this->realName;
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

    public function birthday(): ?Birthday
    {
        return $this->birthday;
    }

    public function career(): Career
    {
        return $this->career;
    }

    public function source(): AutomaticDraftTalentSource
    {
        return $this->source;
    }
}
