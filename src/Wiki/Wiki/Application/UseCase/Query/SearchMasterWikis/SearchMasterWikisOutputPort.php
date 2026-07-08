<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiMasterSearchItemReadModel;

interface SearchMasterWikisOutputPort
{
    /**
     * @param list<WikiMasterSearchItemReadModel> $wikis
     */
    public function output(array $wikis): void;
}
