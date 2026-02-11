<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\ApproveWiki;

use Source\Wiki\Shared\Application\Exception\DuplicateSlugException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\ExistsApprovedDraftWikiException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface ApproveWikiInterface
{
    /**
     * @param ApproveWikiInputPort $input
     * @param ApproveWikiOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws ExistsApprovedDraftWikiException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws DuplicateSlugException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveWikiInputPort $input, ApproveWikiOutputPort $output): void;
}
