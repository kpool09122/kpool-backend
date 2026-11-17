<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;

interface GetGroupDraftWikiInterface
{
    /**
     * @param GetGroupDraftWikiInputPort $input
     * @return DraftWikiReadModel
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws WikiNotFoundException
     */
    public function process(GetGroupDraftWikiInputPort $input): DraftWikiReadModel;
}
