<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetMyAgencyDraftWiki;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;

interface GetMyAgencyDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetMyAgencyDraftWikiInputPort $input): DraftWikiReadModel;
}
