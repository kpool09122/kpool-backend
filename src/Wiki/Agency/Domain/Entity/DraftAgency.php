<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

class DraftAgency
{
    public function __construct(
        private readonly AgencyIdentifier         $agencyIdentifier,
        private ?AgencyIdentifier                 $publishedAgencyIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Slug                     $slug,
        private readonly ?PrincipalIdentifier     $editorIdentifier,
        private readonly Language                 $language,
        private AgencyName                        $name,
        private string                            $normalizedName,
        private CEO                               $CEO,
        private string                            $normalizedCEO,
        private ?FoundedIn                        $foundedIn,
        private Description                       $description,
        private ApprovalStatus                    $status,
        private ?PrincipalIdentifier              $approverIdentifier = null,
        private ?PrincipalIdentifier              $mergerIdentifier = null,
        private ?DateTimeImmutable                $mergedAt = null,
        private ?PrincipalIdentifier              $sourceEditorIdentifier = null,
        private ?DateTimeImmutable                $translatedAt = null,
        private ?DateTimeImmutable                $approvedAt = null,
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

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function editorIdentifier(): ?PrincipalIdentifier
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

    public function setName(AgencyName $name): void
    {
        $this->name = $name;
    }

    public function setNormalizedName(string $normalizedName): void
    {
        $this->normalizedName = $normalizedName;
    }

    public function normalizedName(): string
    {
        return $this->normalizedName;
    }

    public function CEO(): CEO
    {
        return $this->CEO;
    }

    public function setCEO(CEO $CEO): void
    {
        $this->CEO = $CEO;
    }

    public function normalizedCEO(): string
    {
        return $this->normalizedCEO;
    }

    public function setNormalizedCEO(string $normalizedCEO): void
    {
        $this->normalizedCEO = $normalizedCEO;
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

    public function approverIdentifier(): ?PrincipalIdentifier
    {
        return $this->approverIdentifier;
    }

    public function setApproverIdentifier(?PrincipalIdentifier $approverIdentifier): void
    {
        $this->approverIdentifier = $approverIdentifier;
    }

    public function mergerIdentifier(): ?PrincipalIdentifier
    {
        return $this->mergerIdentifier;
    }

    public function setMergerIdentifier(?PrincipalIdentifier $mergerIdentifier): void
    {
        $this->mergerIdentifier = $mergerIdentifier;
    }

    public function mergedAt(): ?DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function setMergedAt(?DateTimeImmutable $mergedAt): void
    {
        $this->mergedAt = $mergedAt;
    }

    public function sourceEditorIdentifier(): ?PrincipalIdentifier
    {
        return $this->sourceEditorIdentifier;
    }

    public function setSourceEditorIdentifier(?PrincipalIdentifier $sourceEditorIdentifier): void
    {
        $this->sourceEditorIdentifier = $sourceEditorIdentifier;
    }

    public function translatedAt(): ?DateTimeImmutable
    {
        return $this->translatedAt;
    }

    public function setTranslatedAt(?DateTimeImmutable $translatedAt): void
    {
        $this->translatedAt = $translatedAt;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?DateTimeImmutable $approvedAt): void
    {
        $this->approvedAt = $approvedAt;
    }
}
