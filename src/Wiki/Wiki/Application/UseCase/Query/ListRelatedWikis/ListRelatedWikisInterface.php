<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface ListRelatedWikisInterface
{
    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws WikiNotFoundException
     */
    public function process(ListRelatedWikisInputPort $input, ListRelatedWikisOutputPort $output): void;
}
