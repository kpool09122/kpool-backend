<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImageDeletion;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestNotPendingException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface RejectImageDeletionInterface
{
    /**
     * @param RejectImageDeletionInputPort $input
     * @param RejectImageDeletionOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws ImageDeletionRequestNotPendingException
     * @throws PrincipalNotFoundException
     */
    public function process(RejectImageDeletionInputPort $input, RejectImageDeletionOutputPort $output): void;
}
