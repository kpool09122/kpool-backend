<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Exception\NgWordDetectedException;

interface TranslateWikiInterface
{
    /**
     * @param TranslateWikiInputPort $input
     * @param TranslateWikiOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws NgWordDetectedException
     * @throws PrincipalNotFoundException
     */
    public function process(TranslateWikiInputPort $input, TranslateWikiOutputPort $output): void;
}
