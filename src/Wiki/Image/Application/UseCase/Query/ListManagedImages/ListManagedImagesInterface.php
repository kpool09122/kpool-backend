<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListManagedImages;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ListManagedImagesInterface
{
    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(ListManagedImagesInputPort $input, ListManagedImagesOutputPort $output): void;
}
