<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyWiki;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;

interface GetAgencyWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetAgencyWikiInputPort $input): WikiReadModel;
}
