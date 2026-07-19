<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;

interface RequestImageDeletionInterface
{
    /**
     * @param RequestImageDeletionInputPort $input
     * @param RequestImageDeletionOutputPort $output
     * @return void
     * @throws ImageNotFoundException
     */
    public function process(RequestImageDeletionInputPort $input, RequestImageDeletionOutputPort $output): void;
}
