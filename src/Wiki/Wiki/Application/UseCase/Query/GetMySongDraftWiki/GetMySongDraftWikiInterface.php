<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetMySongDraftWiki;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;

interface GetMySongDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetMySongDraftWikiInputPort $input): DraftWikiReadModel;
}
