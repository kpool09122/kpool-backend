<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

final readonly class AutomaticDraftAgencyCreationPayload
{
    public function __construct(
        private EditorIdentifier $editorIdentifier,
        private Translation $translation,
        private AgencyName $name,
        private ?CEO $CEO,
        private ?FoundedIn $foundedIn,
        private Description $description,
        private AutomaticDraftAgencySource $source,
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
