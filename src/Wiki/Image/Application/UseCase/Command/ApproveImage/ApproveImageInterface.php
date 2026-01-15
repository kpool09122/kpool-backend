<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;

interface ApproveImageInterface
{
    /**
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     */
    public function process(ApproveImageInputPort $input): Image;
}
