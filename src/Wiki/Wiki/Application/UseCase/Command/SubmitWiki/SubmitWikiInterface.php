<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface SubmitWikiInterface
{
    /**
     * @param SubmitWikiInputPort $input
     * @return DraftWiki
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(SubmitWikiInputPort $input): DraftWiki;
}
