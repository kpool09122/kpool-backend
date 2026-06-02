<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\DeleteWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface DeleteWikiInterface
{
    /**
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(DeleteWikiInputPort $input, DeleteWikiOutputPort $output): void;
}
