<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\TranslationSetMasterSearchItemReadModel;

class SearchTranslationSetMasterWikisOutput implements SearchTranslationSetMasterWikisOutputPort
{
    /** @var list<TranslationSetMasterSearchItemReadModel> */
    private array $translationSetMasters = [];

    /** @param list<TranslationSetMasterSearchItemReadModel> $translationSetMasters */
    public function output(array $translationSetMasters): void
    {
        $this->translationSetMasters = $translationSetMasters;
    }

    /** @return array{translationSetMasters: list<array{translationSetIdentifier: string, resourceType: string, wikis: list<array{wikiIdentifier: string, language: string, name: string, slug: string}>}>} */
    public function toArray(): array
    {
        return [
            'translationSetMasters' => array_map(
                static fn (TranslationSetMasterSearchItemReadModel $translationSetMaster): array => $translationSetMaster->toArray(),
                $this->translationSetMasters,
            ),
        ];
    }
}
