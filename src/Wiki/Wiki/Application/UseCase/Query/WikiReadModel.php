<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class WikiReadModel
{
    private string $wikiIdentifier;
    private string $translationSetIdentifier;
    private string $slug;
    private string $language;
    private string $resourceType;
    private int $version;
    private ?string $themeColor;
    private ?string $title;
    private ?string $metaDescription;
    /** @var list<string>|null */
    private ?array $keywords;
    /** @var array<string, mixed> */
    private array $heroImage;
    private WikiBasicReadModel $basic;
    /** @var list<array<string, mixed>> */
    private array $sections;

    /**
     * @param array<string, mixed> $heroImage
     * @param array<string, mixed>|WikiBasicReadModel $basic
     * @param list<array<string, mixed>> $sections
     * @param list<string>|null $keywords
     */
    public function __construct(
        string $wikiIdentifier,
        string $translationSetIdentifier,
        string $slug,
        string $language,
        string $resourceType,
        int $version,
        ?string $themeColor,
        array $heroImage,
        array|WikiBasicReadModel $basic,
        array $sections,
        ?string $title = null,
        ?string $metaDescription = null,
        ?array $keywords = null,
    ) {
        $this->wikiIdentifier = $wikiIdentifier;
        $this->translationSetIdentifier = $translationSetIdentifier;
        $this->slug = $slug;
        $this->language = $language;
        $this->resourceType = $resourceType;
        $this->version = $version;
        $this->themeColor = $themeColor;
        $this->title = $title;
        $this->metaDescription = $metaDescription;
        $this->keywords = $keywords;
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

    public function version(): int
    {
        return $this->version;
    }

    public function themeColor(): ?string
    {
        return $this->themeColor;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function metaDescription(): ?string
    {
        return $this->metaDescription;
    }

    /**
     * @return list<string>|null
     */
    public function keywords(): ?array
    {
        return $this->keywords;
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
            'version' => $this->version,
            'themeColor' => $this->themeColor,
            'title' => $this->title,
            'metaDescription' => $this->metaDescription,
            'keywords' => $this->keywords,
            'heroImage' => $this->heroImage,
            'basic' => $this->basic->toArray(),
            'sections' => $this->sections,
        ];
    }
}
