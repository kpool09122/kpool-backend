<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImageHideRequest;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Exception\ImageHideRequestNotPendingException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface RejectImageHideRequestInterface
{
    /**
     * @param RejectImageHideRequestInputPort $input
     * @param RejectImageHideRequestOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws ImageHideRequestNotPendingException
     * @throws PrincipalNotFoundException
     */
    public function process(RejectImageHideRequestInputPort $input, RejectImageHideRequestOutputPort $output): void;
}
