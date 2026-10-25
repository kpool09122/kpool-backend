<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

interface ListMyVideoLinkLikesOutputPort
{
    /** @param list<string> $likedVideoLinkIdentifiers */
    public function setLikedVideoLinkIdentifiers(array $likedVideoLinkIdentifiers): void;
}
