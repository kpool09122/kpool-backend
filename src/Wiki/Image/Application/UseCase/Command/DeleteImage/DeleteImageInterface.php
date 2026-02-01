<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\DeleteImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface DeleteImageInterface
{
    /**
     * @param DeleteImageInputPort $input
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(DeleteImageInputPort $input): void;
}
