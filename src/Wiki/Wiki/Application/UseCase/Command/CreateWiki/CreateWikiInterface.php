<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki;

use Source\Wiki\Shared\Application\Exception\DuplicateSlugException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface CreateWikiInterface
{
    /**
     * @param CreateWikiInputPort $input
     * @return DraftWiki
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws DuplicateSlugException
     */
    public function process(CreateWikiInputPort $input): DraftWiki;
}
