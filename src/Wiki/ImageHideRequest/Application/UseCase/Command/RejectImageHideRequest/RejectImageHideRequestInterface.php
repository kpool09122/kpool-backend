<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\RejectImageHideRequest;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
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
     * @throws PrincipalNotFoundException
     */
    public function process(RejectImageHideRequestInputPort $input, RejectImageHideRequestOutputPort $output): void;
}
