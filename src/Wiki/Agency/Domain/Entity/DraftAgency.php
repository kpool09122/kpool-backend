<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Entity;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

class DraftAgency
{
    public function __construct(
        private readonly AgencyIdentifier  $agencyIdentifier,
        private ?AgencyIdentifier          $publishedAgencyIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly EditorIdentifier $editorIdentifier,
        private readonly Translation      $translation,
        private AgencyName                $name,
        private CEO                       $CEO,
        private ?FoundedIn                $foundedIn,
        private Description               $description,
        private ApprovalStatus            $status,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function publishedAgencyIdentifier(): ?AgencyIdentifier
    {
        return $this->publishedAgencyIdentifier;
    }

    public function setPublishedAgencyIdentifier(AgencyIdentifier $agencyIdentifier): void
    {
        $this->publishedAgencyIdentifier = $agencyIdentifier;
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

    public function name(): AgencyName
    {
        return $this->name;
    }

    public function setName(AgencyName $name): void
    {
        $this->name = $name;
    }

    public function CEO(): ?CEO
    {
        return $this->CEO;
    }

    public function setCEO(CEO $CEO): void
    {
        $this->CEO = $CEO;
    }

    public function foundedIn(): ?FoundedIn
    {
        return $this->foundedIn;
    }

    public function setFoundedIn(FoundedIn $foundedIn): void
    {
        $this->foundedIn = $foundedIn;
    }

    public function description(): Description
    {
        return $this->description;
    }

    public function setDescription(Description $description): void
    {
        $this->description = $description;
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
