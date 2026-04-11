<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface RejectImageInterface
{
    /**
     * @param RejectImageInputPort $input
     * @param RejectImageOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function process(RejectImageInputPort $input, RejectImageOutputPort $output): void;
}
