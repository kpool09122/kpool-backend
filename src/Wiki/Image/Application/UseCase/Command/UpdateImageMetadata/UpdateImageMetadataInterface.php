<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface UpdateImageMetadataInterface
{
    /**
     * @param UpdateImageMetadataInputPort $input
     * @param UpdateImageMetadataOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(UpdateImageMetadataInputPort $input, UpdateImageMetadataOutputPort $output): void;
}
