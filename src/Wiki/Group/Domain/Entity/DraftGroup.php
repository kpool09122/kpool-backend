<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Entity;

use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

class DraftGroup
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param GroupIdentifier|null $publishedGroupIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param EditorIdentifier $editorIdentifier
     * @param Translation $translation
     * @param GroupName $name
     * @param AgencyIdentifier|null $agencyIdentifier
     * @param Description $description
     * @param SongIdentifier[] $songIdentifiers
     * @param ImagePath|null $imageLink
     * @param ApprovalStatus $status
     */
    public function __construct(
        private readonly GroupIdentifier $groupIdentifier,
        private ?GroupIdentifier $publishedGroupIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly EditorIdentifier $editorIdentifier,
        private readonly Translation $translation,
        private GroupName $name,
        private ?AgencyIdentifier $agencyIdentifier,
        private Description $description,
        private array $songIdentifiers,
        private ?ImagePath $imageLink,
        private ApprovalStatus $status,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function publishedGroupIdentifier(): ?GroupIdentifier
    {
        return $this->publishedGroupIdentifier;
    }

    public function setPublishedGroupIdentifier(GroupIdentifier $groupIdentifier): void
    {
        $this->publishedGroupIdentifier = $groupIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
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

    public function setName(GroupName $name): void
    {
        $this->name = $name;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function setAgencyIdentifier(AgencyIdentifier $agencyIdentifier): void
    {
        $this->agencyIdentifier = $agencyIdentifier;
    }

    public function description(): Description
    {
        return $this->description;
    }

    public function setDescription(Description $description): void
    {
        $this->description = $description;
    }

    /**
     * @return list<SongIdentifier>
     */
    public function songIdentifiers(): array
    {
        return $this->songIdentifiers;
    }

    /**
     * @param list<SongIdentifier> $songIdentifiers
     * @return void
     */
    public function setSongIdentifiers(array $songIdentifiers): void
    {
        $this->songIdentifiers = $songIdentifiers;
    }

    public function imageLink(): ?ImagePath
    {
        return $this->imageLink;
    }

    public function setImageLink(?ImagePath $imageLink): void
    {
        $this->imageLink = $imageLink;
    }

    public function status(): ApprovalStatus
    {
        return $this->status;
    }

    public function setStatus(ApprovalStatus $status): void
    {
        $this->status = $status;
    }
}
