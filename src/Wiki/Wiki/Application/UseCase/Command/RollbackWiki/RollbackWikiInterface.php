<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidRollbackTargetVersionException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\SnapshotNotFoundException;
use Source\Wiki\Shared\Domain\Exception\VersionMismatchException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface RollbackWikiInterface
{
    /**
     * @param RollbackWikiInputPort $input
     * @param RollbackWikiOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws SnapshotNotFoundException
     * @throws VersionMismatchException
     * @throws InvalidRollbackTargetVersionException
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function process(RollbackWikiInputPort $input, RollbackWikiOutputPort $output): void;
}
