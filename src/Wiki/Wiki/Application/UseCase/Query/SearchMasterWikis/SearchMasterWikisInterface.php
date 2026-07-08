<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis;

interface SearchMasterWikisInterface
{
    public function process(SearchMasterWikisInputPort $input, SearchMasterWikisOutputPort $output): void;
}
