<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

interface ListRelatedWikisOutputPort
{
    /** @param list<WikiListItemReadModel> $wikis */
    public function output(array $wikis): void;
}
