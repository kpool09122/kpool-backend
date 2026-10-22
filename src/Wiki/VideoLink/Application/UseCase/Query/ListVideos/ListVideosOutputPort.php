<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos;

use Source\Wiki\VideoLink\Application\UseCase\Query\VideoLinkReadModel;

interface ListVideosOutputPort
{
    /** @param list<VideoLinkReadModel> $videos */
    public function output(array $videos): void;
}
