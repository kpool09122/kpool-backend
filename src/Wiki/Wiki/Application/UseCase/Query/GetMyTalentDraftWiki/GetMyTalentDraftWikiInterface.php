<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetMyTalentDraftWiki;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;

interface GetMyTalentDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetMyTalentDraftWikiInputPort $input): DraftWikiReadModel;
}
