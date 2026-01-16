<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;

interface RejectImageInterface
{
    /**
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     */
    public function process(RejectImageInputPort $input): DraftImage;
}
