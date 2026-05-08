<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages;

interface ListUploadedImagesInterface
{
    public function process(ListUploadedImagesInputPort $input, ListUploadedImagesOutputPort $output): void;
}
