<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class AgencyDraftWikiReadModel
{
    /**
     * @param array<string, mixed> $heroImage
     * @param array<string, mixed> $basic
     * @param list<array<string, mixed>> $sections
     */
    public function __construct(
        private string $wikiIdentifier,
        private string $slug,
        private string $language,
        private string $resourceType,
        private int $version,
        private ?string $themeColor,
        private array $heroImage,
        private array $basic,
        private array $sections,
    ) {
    }

    public function wikiIdentifier(): string
    {
        return $this->wikiIdentifier;
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

    /**
     * @return array<string, mixed>
     */
    public function heroImage(): array
    {
        return $this->heroImage;
    }

    /**
     * @return array<string, mixed>
     */
    public function basic(): array
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
            'slug' => $this->slug,
            'language' => $this->language,
            'resourceType' => $this->resourceType,
            'version' => $this->version,
            'themeColor' => $this->themeColor,
            'heroImage' => $this->heroImage,
            'basic' => $this->basic,
            'sections' => $this->sections,
        ];
    }
}
