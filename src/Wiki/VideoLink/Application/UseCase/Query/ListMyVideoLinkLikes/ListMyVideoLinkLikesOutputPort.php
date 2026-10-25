<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

use Source\Wiki\VideoLink\Application\UseCase\Query\LikedVideoLinkReadModel;

interface ListMyVideoLinkLikesOutputPort
{
    /**
     * @param list<string> $likedVideoLinkIdentifiers
     * @param list<LikedVideoLinkReadModel> $videos
     * @param array{currentPage:int,perPage:int,total:int}|null $pagination
     */
    public function output(array $likedVideoLinkIdentifiers, array $videos, ?array $pagination = null): void;
}
