<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class TranslationSetMasterSearchWikiReadModel
{
    public function __construct(private string $wikiIdentifier, private string $language, private string $name, private string $slug)
    {
    }

    /** @return array{wikiIdentifier: string, language: string, name: string, slug: string} */
    public function toArray(): array
    {
        return ['wikiIdentifier' => $this->wikiIdentifier, 'language' => $this->language, 'name' => $this->name, 'slug' => $this->slug];
    }
}
