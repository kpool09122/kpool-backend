<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\InconsistentVersionException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\Wiki;

interface PublishWikiInterface
{
    /**
     * @param PublishWikiInputPort $input
     * @return Wiki
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws InconsistentVersionException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(PublishWikiInputPort $input): Wiki;
}
