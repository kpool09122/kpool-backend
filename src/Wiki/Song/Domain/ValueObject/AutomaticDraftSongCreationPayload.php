<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;

final readonly class AutomaticDraftSongCreationPayload
{
    public function __construct(
        private Language           $language,
        private SongName           $name,
        private ?AgencyIdentifier  $agencyIdentifier,
        private ?GroupIdentifier   $groupIdentifier,
        private ?TalentIdentifier  $talentIdentifier,
    ) {
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): SongName
    {
        return $this->name;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function groupIdentifier(): ?GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function talentIdentifier(): ?TalentIdentifier
    {
        return $this->talentIdentifier;
    }
}
