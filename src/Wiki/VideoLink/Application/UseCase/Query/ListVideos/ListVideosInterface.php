<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface ListVideosInterface
{
    /** @throws WikiNotFoundException */
    public function process(ListVideosInputPort $input, ListVideosOutputPort $output): void;
}
