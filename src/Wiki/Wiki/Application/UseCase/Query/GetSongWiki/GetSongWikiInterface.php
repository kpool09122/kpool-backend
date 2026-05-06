<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetSongWiki;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;

interface GetSongWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetSongWikiInputPort $input): WikiReadModel;
}
