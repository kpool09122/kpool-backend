<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestNotPendingException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ApproveImageDeletionInterface
{
    /**
     * @param ApproveImageDeletionInputPort $input
     * @param ApproveImageDeletionOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws ImageDeletionRequestNotPendingException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveImageDeletionInputPort $input, ApproveImageDeletionOutputPort $output): void;
}
