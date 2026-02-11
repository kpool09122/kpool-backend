<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\MergeWiki;

use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface MergeWikiInterface
{
    /**
     * @param MergeWikiInputPort $input
     * @return DraftWiki
     * @throws WikiNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(MergeWikiInputPort $input): DraftWiki;
}
