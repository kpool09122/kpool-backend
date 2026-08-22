<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis;

interface ListMyOwnedWikisInterface
{
    public function process(ListMyOwnedWikisInputPort $input, ListMyOwnedWikisOutputPort $output): void;
}
