<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis;

interface ListMyDraftWikisInterface
{
    public function process(ListMyDraftWikisInputPort $input, ListMyDraftWikisOutputPort $output): void;
}
