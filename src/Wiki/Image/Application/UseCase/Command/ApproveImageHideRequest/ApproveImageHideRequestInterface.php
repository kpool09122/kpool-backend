<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Exception\ImageHideRequestNotPendingException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ApproveImageHideRequestInterface
{
    /**
     * @param ApproveImageHideRequestInputPort $input
     * @param ApproveImageHideRequestOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws ImageHideRequestNotPendingException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveImageHideRequestInputPort $input, ApproveImageHideRequestOutputPort $output): void;
}
