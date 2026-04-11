<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ApproveImageInterface
{
    /**
     * @param ApproveImageInputPort $input
     * @param ApproveImageOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveImageInputPort $input, ApproveImageOutputPort $output): void;
}
