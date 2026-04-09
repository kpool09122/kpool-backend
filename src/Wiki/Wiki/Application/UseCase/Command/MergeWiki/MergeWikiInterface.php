<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\MergeWiki;

use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface MergeWikiInterface
{
    /**
     * @param MergeWikiInputPort $input
     * @param MergeWikiOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(MergeWikiInputPort $input, MergeWikiOutputPort $output): void;
}
