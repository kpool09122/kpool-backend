<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;

interface RequestImageHideInterface
{
    /**
     * @param RequestImageHideInputPort $input
     * @return ImageHideRequest
     * @throws ImageNotFoundException
     */
    public function process(RequestImageHideInputPort $input): ImageHideRequest;
}
