<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;

interface RequestImageHideInterface
{
    /**
     * @param RequestImageHideInputPort $input
     * @param RequestImageHideOutputPort $output
     * @return void
     * @throws ImageNotFoundException
     */
    public function process(RequestImageHideInputPort $input, RequestImageHideOutputPort $output): void;
}
