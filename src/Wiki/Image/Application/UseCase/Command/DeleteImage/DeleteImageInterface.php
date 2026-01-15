<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\DeleteImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;

interface DeleteImageInterface
{
    /**
     * @throws ImageNotFoundException
     */
    public function process(DeleteImageInputPort $input): void;
}
