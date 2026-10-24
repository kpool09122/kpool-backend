<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

interface ListMyVideoLinkLikesInterface
{
    public function process(ListMyVideoLinkLikesInputPort $input, ListMyVideoLinkLikesOutputPort $output): void;
}
