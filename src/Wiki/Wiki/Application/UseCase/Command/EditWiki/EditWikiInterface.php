<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\EditWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface EditWikiInterface
{
    /**
     * @param EditWikiInputPort $input
     * @return DraftWiki
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(EditWikiInputPort $input): DraftWiki;
}
