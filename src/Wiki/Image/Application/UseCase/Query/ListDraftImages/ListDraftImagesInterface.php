<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListDraftImages;

interface ListDraftImagesInterface
{
    public function process(ListDraftImagesInputPort $input, ListDraftImagesOutputPort $output): void;
}
