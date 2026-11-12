<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki;

use Source\Wiki\Shared\Application\Exception\DuplicateSlugException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Domain\Exception\NgWordDetectedException;

interface AutoCreateWikiInterface
{
    /**
     * @param AutoCreateWikiInputPort $input
     * @param AutoCreateWikiOutputPort $output
     * @return void
     * @throws DuplicateSlugException
     * @throws DisallowedException
     * @throws NgWordDetectedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateWikiInputPort $input, AutoCreateWikiOutputPort $output): void;
}
