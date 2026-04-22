<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\SongDraftWikiReadModel;

interface GetSongDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetSongDraftWikiInputPort $input): SongDraftWikiReadModel;
}
