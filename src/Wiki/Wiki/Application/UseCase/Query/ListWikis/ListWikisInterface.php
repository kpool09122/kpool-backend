<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListWikis;

interface ListWikisInterface
{
    public function process(ListWikisInputPort $input, ListWikisOutputPort $output): void;
}
