<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListManagedImages;

use Source\Wiki\Image\Application\UseCase\Query\ManagedImageReadModel;

interface ListManagedImagesOutputPort
{
    /**
     * @param list<ManagedImageReadModel> $images
     */
    public function output(array $images, int $currentPage, int $lastPage, int $total, int $perPage): void;
}
