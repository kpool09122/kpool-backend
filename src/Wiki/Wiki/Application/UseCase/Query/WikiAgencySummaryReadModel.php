<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class WikiAgencySummaryReadModel
{
    use ArrayAccessibleReadModel;

    public function __construct(
        private string $wikiIdentifier,
        private string $slug,
        private string $language,
        private string $name,
        private string $normalizedName,
    ) {
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
            'name' => $this->name,
            'normalizedName' => $this->normalizedName,
        ];
    }
}
