<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials;

use InvalidArgumentException;
use Source\Wiki\Image\Application\Exception\MaterialSearchFailedException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface SearchFreeMaterialsInterface
{
    /**
     * @throws InvalidArgumentException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws MaterialSearchFailedException
     */
    public function process(SearchFreeMaterialsInputPort $input, SearchFreeMaterialsOutputPort $output): void;
}
