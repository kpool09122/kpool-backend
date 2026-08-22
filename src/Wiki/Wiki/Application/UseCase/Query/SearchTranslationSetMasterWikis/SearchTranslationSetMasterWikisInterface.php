<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis;

interface SearchTranslationSetMasterWikisInterface
{
    public function process(SearchTranslationSetMasterWikisInputPort $input, SearchTranslationSetMasterWikisOutputPort $output): void;
}
