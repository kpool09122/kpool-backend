<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\ApproveImageHideRequest;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ApproveImageHideRequestInterface
{
    /**
     * @param ApproveImageHideRequestInputPort $input
     * @return ImageHideRequest
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveImageHideRequestInputPort $input): ImageHideRequest;
}
