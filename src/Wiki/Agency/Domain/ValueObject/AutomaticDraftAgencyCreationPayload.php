<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

final readonly class AutomaticDraftAgencyCreationPayload
{
    public function __construct(
        private PrincipalIdentifier        $editorIdentifier,
        private Language                   $language,
        private AgencyName                 $name,
        private ?CEO                       $CEO,
        private ?FoundedIn                 $foundedIn,
        private Description                $description,
        private AutomaticDraftAgencySource $source,
    ) {
    }

    public function editorIdentifier(): PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): AgencyName
    {
        return $this->name;
    }

    public function CEO(): ?CEO
    {
        return $this->CEO;
    }

    public function foundedIn(): ?FoundedIn
    {
        return $this->foundedIn;
    }

    public function description(): Description
    {
        return $this->description;
    }

    public function source(): AutomaticDraftAgencySource
    {
        return $this->source;
    }
}
