<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UnhideImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface UnhideImageInterface
{
    /**
     * @param UnhideImageInputPort $input
     * @param UnhideImageOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(UnhideImageInputPort $input, UnhideImageOutputPort $output): void;
}
