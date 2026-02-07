<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface TranslateWikiInterface
{
    /**
     * @param TranslateWikiInputPort $input
     * @return DraftWiki[]
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(TranslateWikiInputPort $input): array;
}
