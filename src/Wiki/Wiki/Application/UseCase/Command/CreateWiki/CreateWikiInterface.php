<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki;

use Source\Wiki\Shared\Application\Exception\DuplicateSlugException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface CreateWikiInterface
{
    /**
     * @param CreateWikiInputPort $input
     * @param CreateWikiOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws DuplicateSlugException
     */
    public function process(CreateWikiInputPort $input, CreateWikiOutputPort $output): void;
}
