<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListPopularVideos;

use Source\Wiki\VideoLink\Application\UseCase\Query\PopularVideoReadModel;

interface ListPopularVideosInterface
{
    /** @return list<PopularVideoReadModel> */
    public function process(): array;
}
