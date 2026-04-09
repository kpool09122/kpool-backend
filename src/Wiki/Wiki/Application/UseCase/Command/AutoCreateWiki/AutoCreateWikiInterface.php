<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface AutoCreateWikiInterface
{
    /**
     * @param AutoCreateWikiInputPort $input
     * @param AutoCreateWikiOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateWikiInputPort $input, AutoCreateWikiOutputPort $output): void;
}
