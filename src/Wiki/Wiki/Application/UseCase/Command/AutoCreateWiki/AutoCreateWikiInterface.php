<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface AutoCreateWikiInterface
{
    /**
     * @param AutoCreateWikiInputPort $input
     * @return DraftWiki
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateWikiInputPort $input): DraftWiki;
}
