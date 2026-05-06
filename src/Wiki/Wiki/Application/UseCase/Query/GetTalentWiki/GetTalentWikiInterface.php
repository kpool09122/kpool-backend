<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetTalentWiki;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;

interface GetTalentWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetTalentWikiInputPort $input): WikiReadModel;
}
