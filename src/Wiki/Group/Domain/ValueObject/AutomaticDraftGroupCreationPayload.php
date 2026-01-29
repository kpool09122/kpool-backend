<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Language;

final readonly class AutomaticDraftGroupCreationPayload
{
    public function __construct(
        private Language                  $language,
        private GroupName                 $name,
        private ?AgencyIdentifier          $agencyIdentifier,
    ) {
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): GroupName
    {
        return $this->name;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }
}
