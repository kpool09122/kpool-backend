<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyDraftWiki;

use Source\Wiki\Wiki\Application\UseCase\Query\AgencyDraftWikiReadModel;

interface GetAgencyDraftWikiInterface
{
    public function process(GetAgencyDraftWikiInputPort $input): AgencyDraftWikiReadModel;
}
