<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyDraftWiki;

use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;

interface GetAgencyDraftWikiInterface
{
    public function process(GetAgencyDraftWikiInputPort $input): DraftWikiReadModel;
}
