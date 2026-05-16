<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis;

interface ListDraftWikisInterface
{
    public function process(ListDraftWikisInputPort $input, ListDraftWikisOutputPort $output): void;
}
