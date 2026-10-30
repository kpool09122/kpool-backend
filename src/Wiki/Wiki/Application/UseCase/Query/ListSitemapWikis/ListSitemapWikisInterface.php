<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis;

interface ListSitemapWikisInterface
{
    public function process(ListSitemapWikisInputPort $input, ListSitemapWikisOutputPort $output): void;
}
