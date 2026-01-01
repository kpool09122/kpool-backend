<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

final readonly class AutomaticDraftGroupCreationPayload
{
    public function __construct(
        private PrincipalIdentifier       $editorIdentifier,
        private Language                  $language,
        private GroupName                 $name,
        private AgencyIdentifier          $agencyIdentifier,
        private Description               $description,
        private AutomaticDraftGroupSource $source,
    ) {
    }

    public function editorIdentifier(): PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function translation(): Language
    {
        return $this->language;
    }

    public function name(): GroupName
    {
        return $this->name;
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function description(): Description
    {
        return $this->description;
    }

    public function source(): AutomaticDraftGroupSource
    {
        return $this->source;
    }
}
