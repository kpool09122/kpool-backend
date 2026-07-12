<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiFontStyle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiSnapshotIdentifier;

readonly class WikiSnapshot
{
    public function __construct(
        private WikiSnapshotIdentifier   $snapshotIdentifier,
        private WikiIdentifier           $wikiIdentifier,
        private TranslationSetIdentifier $translationSetIdentifier,
        private Slug                     $slug,
        private Language                 $language,
        private ResourceType             $resourceType,
        private BasicInterface           $basic,
        private SectionContentCollection $sections,
        private ?Color                   $themeColor,
        private Version                  $version,
        private ?PrincipalIdentifier     $editorIdentifier,
        private ?PrincipalIdentifier     $approverIdentifier,
        private ?PrincipalIdentifier     $mergerIdentifier,
        private ?PrincipalIdentifier     $sourceEditorIdentifier,
        private ?DateTimeImmutable       $mergedAt,
        private ?DateTimeImmutable       $translatedAt,
        private ?DateTimeImmutable       $approvedAt,
        private DateTimeImmutable        $createdAt,
        private ?ImageIdentifier         $imageIdentifier = null,
        private ?SeoTitle                $title = null,
        private ?MetaDescription         $metaDescription = null,
        private ?SeoKeywords             $keywords = null,
        private ?WikiFontStyle           $fontStyle = null,
    ) {
    }

    public function snapshotIdentifier(): WikiSnapshotIdentifier
    {
        return $this->snapshotIdentifier;
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

    public function sections(): SectionContentCollection
    {
        return $this->sections;
    }

    public function themeColor(): ?Color
    {
        return $this->themeColor;
    }

    public function fontStyle(): ?WikiFontStyle
    {
        return $this->fontStyle;
    }

    public function title(): ?SeoTitle
    {
        return $this->title;
    }

    public function metaDescription(): ?MetaDescription
    {
        return $this->metaDescription;
    }

    /**
     * @return SeoKeywords|null
     */
    public function keywords(): ?SeoKeywords
    {
        return $this->keywords;
    }

    public function version(): Version
    {
        return $this->version;
    }

    public function editorIdentifier(): ?PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function approverIdentifier(): ?PrincipalIdentifier
    {
        return $this->approverIdentifier;
    }

    public function mergerIdentifier(): ?PrincipalIdentifier
    {
        return $this->mergerIdentifier;
    }

    public function sourceEditorIdentifier(): ?PrincipalIdentifier
    {
        return $this->sourceEditorIdentifier;
    }

    public function mergedAt(): ?DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function translatedAt(): ?DateTimeImmutable
    {
        return $this->translatedAt;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function imageIdentifier(): ?ImageIdentifier
    {
        return $this->imageIdentifier;
    }
}
