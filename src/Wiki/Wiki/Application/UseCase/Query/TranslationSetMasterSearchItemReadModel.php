<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class TranslationSetMasterSearchItemReadModel
{
    /** @param list<TranslationSetMasterSearchWikiReadModel> $wikis */
    public function __construct(private string $translationSetIdentifier, private string $resourceType, private array $wikis)
    {
    }

    /** @return array{translationSetIdentifier: string, resourceType: string, wikis: list<array{wikiIdentifier: string, language: string, name: string, slug: string}>} */
    public function toArray(): array
    {
        return [
            'translationSetIdentifier' => $this->translationSetIdentifier,
            'resourceType' => $this->resourceType,
            'wikis' => array_map(static fn (TranslationSetMasterSearchWikiReadModel $wiki): array => $wiki->toArray(), $this->wikis),
        ];
    }
}
