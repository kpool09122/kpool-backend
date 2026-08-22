<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\TranslationSetMasterSearchItemReadModel;

interface SearchTranslationSetMasterWikisOutputPort
{
    /** @param list<TranslationSetMasterSearchItemReadModel> $translationSetMasters */
    public function output(array $translationSetMasters): void;

    /** @return array{translationSetMasters: list<array{translationSetIdentifier: string, resourceType: string, wikis: list<array{wikiIdentifier: string, language: string, name: string, slug: string}>}>} */
    public function toArray(): array;
}
