<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetMyGroupDraftWiki;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;

interface GetMyGroupDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetMyGroupDraftWikiInputPort $input): DraftWikiReadModel;
}
