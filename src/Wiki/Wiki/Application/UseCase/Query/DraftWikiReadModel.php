<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class DraftWikiReadModel
{
    private string $wikiIdentifier;
    private string $translationSetIdentifier;
    private string $slug;
    private string $language;
    private string $resourceType;
    private ?string $themeColor;
    /** @var array<string, mixed> */
    private array $heroImage;
    private WikiBasicReadModel $basic;
    /** @var list<array<string, mixed>> */
    private array $sections;

    /**
     * @param array<string, mixed> $heroImage
     * @param array<string, mixed>|WikiBasicReadModel $basic
     * @param list<array<string, mixed>> $sections
     */
    public function __construct(
        string $wikiIdentifier,
        string $translationSetIdentifier,
        string $slug,
        string $language,
        string $resourceType,
        ?string $themeColor,
        array $heroImage,
        array|WikiBasicReadModel $basic,
        array $sections,
    ) {
        $this->wikiIdentifier = $wikiIdentifier;
        $this->translationSetIdentifier = $translationSetIdentifier;
        $this->slug = $slug;
        $this->language = $language;
        $this->resourceType = $resourceType;
        $this->themeColor = $themeColor;
        $this->heroImage = $heroImage;
        $this->basic = is_array($basic) ? match ($resourceType) {
            'group' => WikiBasicReadModelFactory::group($basic),
            'talent' => WikiBasicReadModelFactory::talent($basic),
            'song' => WikiBasicReadModelFactory::song($basic),
            'agency' => WikiBasicReadModelFactory::agency($basic),
            default => throw new \InvalidArgumentException("Unsupported resource type: {$resourceType}"),
        } : $basic;
        $this->sections = $sections;
    }

    public function wikiIdentifier(): string
    {
        return $this->wikiIdentifier;
    }

    public function translationSetIdentifier(): string
    {
        return $this->translationSetIdentifier;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function resourceType(): string
    {
        return $this->resourceType;
    }

    public function themeColor(): ?string
    {
        return $this->themeColor;
    }

    /**
     * @return array<string, mixed>
     */
    public function heroImage(): array
    {
        return $this->heroImage;
    }

    /**
     */
    public function basic(): WikiBasicReadModel
    {
        return $this->basic;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'wikiIdentifier' => $this->wikiIdentifier,
            'translationSetIdentifier' => $this->translationSetIdentifier,
            'slug' => $this->slug,
            'language' => $this->language,
            'resourceType' => $this->resourceType,
            'themeColor' => $this->themeColor,
            'heroImage' => $this->heroImage,
            'basic' => $this->basic->toArray(),
            'sections' => $this->sections,
        ];
    }
}
