<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;

interface GetGroupWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetGroupWikiInputPort $input): WikiReadModel;
}
