<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis;

interface ListVersionInconsistentWikisInterface
{
    public function process(
        ListVersionInconsistentWikisInputPort $input,
        ListVersionInconsistentWikisOutputPort $output,
    ): void;
}
