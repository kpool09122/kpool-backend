<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\SitemapWikiReadModel;

interface ListSitemapWikisOutputPort
{
    /** @param list<SitemapWikiReadModel> $wikis */
    public function output(array $wikis, ?string $nextCursor): void;
}
