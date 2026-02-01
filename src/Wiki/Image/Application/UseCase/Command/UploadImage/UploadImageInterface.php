<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UploadImage;

use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface UploadImageInterface
{
    /**
     * @param UploadImageInputPort $input
     * @return DraftImage
     * @throws InvalidBase64ImageException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(UploadImageInputPort $input): DraftImage;
}
