<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

final readonly class AutomaticDraftGroupCreationPayload
{
    /**
     * @param SongIdentifier[] $songIdentifiers
     */
    public function __construct(
        private EditorIdentifier $editorIdentifier,
        private Translation $translation,
        private GroupName $name,
        private AgencyIdentifier $agencyIdentifier,
        private Description $description,
        private array $songIdentifiers,
        private AutomaticDraftGroupSource $source,
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

    /**
     * @return SongIdentifier[]
     */
    public function songIdentifiers(): array
    {
        return $this->songIdentifiers;
    }

    public function source(): AutomaticDraftGroupSource
    {
        return $this->source;
    }
}
