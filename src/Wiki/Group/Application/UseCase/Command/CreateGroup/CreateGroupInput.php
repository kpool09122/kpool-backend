<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\CreateGroup;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

readonly class CreateGroupInput implements CreateGroupInputPort
{
    /**
     * @param EditorIdentifier $editorIdentifier
     * @param GroupIdentifier|null $publishedGroupIdentifier
     * @param Translation $translation
     * @param GroupName $name
     * @param AgencyIdentifier $agencyIdentifier
     * @param Description $description
     * @param SongIdentifier[] $songIdentifiers
     * @param string|null $base64EncodedImage
     * @param Principal $principal
     */
    public function __construct(
        private EditorIdentifier $editorIdentifier,
        private ?GroupIdentifier $publishedGroupIdentifier,
        private Translation $translation,
        private GroupName $name,
        private AgencyIdentifier $agencyIdentifier,
        private Description $description,
        private array $songIdentifiers,
        private ?string $base64EncodedImage,
        private Principal $principal,
    ) {
    }

    public function editorIdentifier(): EditorIdentifier
    {
        return $this->editorIdentifier;
    }

    public function publishedGroupIdentifier(): ?GroupIdentifier
    {
        return $this->publishedGroupIdentifier;
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
     * @return list<SongIdentifier>
     */
    public function songIdentifiers(): array
    {
        return $this->songIdentifiers;
    }

    public function base64EncodedImage(): ?string
    {
        return $this->base64EncodedImage;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
