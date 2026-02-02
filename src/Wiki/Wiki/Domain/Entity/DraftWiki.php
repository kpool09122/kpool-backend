<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

class DraftWiki
{
    public function __construct(
        private readonly WikiIdentifier $wikiIdentifier,
        private ?WikiIdentifier $publishedWikiIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Slug $slug,
        private readonly Language $language,
        private readonly ResourceType $resourceType,
        private BasicInterface $basic,
        private SectionContentCollection $sections,
        private ?Color $themeColor,
        private ApprovalStatus $status,
        private readonly ?PrincipalIdentifier $editorIdentifier = null,
        private ?PrincipalIdentifier $approverIdentifier = null,
        private ?PrincipalIdentifier $mergerIdentifier = null,
        private ?PrincipalIdentifier $sourceEditorIdentifier = null,
        private ?DateTimeImmutable $mergedAt = null,
        private ?DateTimeImmutable $translatedAt = null,
        private ?DateTimeImmutable $approvedAt = null,
    ) {
    }

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function publishedWikiIdentifier(): ?WikiIdentifier
    {
        return $this->publishedWikiIdentifier;
    }

    public function setPublishedWikiIdentifier(WikiIdentifier $wikiIdentifier): void
    {
        $this->publishedWikiIdentifier = $wikiIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function basic(): BasicInterface
    {
        return $this->basic;
    }

    public function setBasic(BasicInterface $basic): void
    {
        $this->basic = $basic;
    }

    public function sections(): SectionContentCollection
    {
        return $this->sections;
    }

    public function setSections(SectionContentCollection $sections): void
    {
        $this->sections = $sections;
    }

    public function themeColor(): ?Color
    {
        return $this->themeColor;
    }

    public function setThemeColor(?Color $themeColor): void
    {
        $this->themeColor = $themeColor;
    }

    public function status(): ApprovalStatus
    {
        return $this->status;
    }

    public function setStatus(ApprovalStatus $status): void
    {
        $this->status = $status;
    }

    public function editorIdentifier(): ?PrincipalIdentifier
    {
        return $this->editorIdentifier;
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

    public function sourceEditorIdentifier(): ?PrincipalIdentifier
    {
        return $this->sourceEditorIdentifier;
    }

    public function setSourceEditorIdentifier(?PrincipalIdentifier $sourceEditorIdentifier): void
    {
        $this->sourceEditorIdentifier = $sourceEditorIdentifier;
    }

    public function mergedAt(): ?DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function setMergedAt(?DateTimeImmutable $mergedAt): void
    {
        $this->mergedAt = $mergedAt;
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
