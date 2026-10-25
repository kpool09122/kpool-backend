<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

class ListMyVideoLinkLikesOutput implements ListMyVideoLinkLikesOutputPort
{
    /** @var list<string> */
    private array $likedVideoLinkIdentifiers = [];

    /** @param list<string> $likedVideoLinkIdentifiers */
    public function setLikedVideoLinkIdentifiers(array $likedVideoLinkIdentifiers): void
    {
        $this->likedVideoLinkIdentifiers = $likedVideoLinkIdentifiers;
    }

    /** @return array{likedVideoLinkIdentifiers:list<string>} */
    public function toArray(): array
    {
        return ['likedVideoLinkIdentifiers' => $this->likedVideoLinkIdentifiers];
    }
}
