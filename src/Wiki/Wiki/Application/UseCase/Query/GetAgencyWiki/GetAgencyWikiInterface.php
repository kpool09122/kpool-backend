<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyWiki;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;

interface GetAgencyWikiInterface
{
    public function process(GetAgencyWikiInputPort $input): WikiReadModel;
}
