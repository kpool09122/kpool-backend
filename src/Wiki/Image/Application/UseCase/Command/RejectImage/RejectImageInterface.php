<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface RejectImageInterface
{
    /**
     * @param RejectImageInputPort $input
     * @return DraftImage
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function process(RejectImageInputPort $input): DraftImage;
}
