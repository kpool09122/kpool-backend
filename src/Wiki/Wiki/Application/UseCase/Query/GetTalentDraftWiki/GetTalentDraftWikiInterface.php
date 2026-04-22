<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentDraftWikiReadModel;

interface GetTalentDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetTalentDraftWikiInputPort $input): TalentDraftWikiReadModel;
}
