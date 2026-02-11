<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

class Wiki
{
    public function __construct(
        private readonly WikiIdentifier $wikiIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Slug $slug,
        private readonly Language $language,
        private readonly ResourceType $resourceType,
        private BasicInterface $basic,
        private SectionContentCollection $sections,
        private ?Color $themeColor,
        private Version $version,
        private ?AccountIdentifier $ownerAccountIdentifier = null,
        private ?PrincipalIdentifier $editorIdentifier = null,
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

    public function version(): Version
    {
        return $this->version;
    }

    public function updateVersion(): void
    {
        $this->version = Version::nextVersion($this->version);
    }

    public function hasSameVersion(Version $version): bool
    {
        return $this->version->value() === $version->value();
    }

    public function isVersionGreaterThan(Version $version): bool
    {
        return $this->version->value() > $version->value();
    }

    public function ownerAccountIdentifier(): ?AccountIdentifier
    {
        return $this->ownerAccountIdentifier;
    }

    public function isOfficial(): bool
    {
        return $this->ownerAccountIdentifier !== null;
    }

    public function markOfficial(AccountIdentifier $ownerAccountIdentifier): void
    {
        if ($this->isOfficial()) {
            return;
        }
        $this->ownerAccountIdentifier = $ownerAccountIdentifier;
    }

    public function editorIdentifier(): ?PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function setEditorIdentifier(?PrincipalIdentifier $editorIdentifier): void
    {
        $this->editorIdentifier = $editorIdentifier;
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
