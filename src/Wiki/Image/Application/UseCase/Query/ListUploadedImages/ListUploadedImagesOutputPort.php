<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages;

use Source\Wiki\Image\Application\UseCase\Query\UploadedImageReadModel;

interface ListUploadedImagesOutputPort
{
    /**
     * @param list<UploadedImageReadModel> $images
     */
    public function output(array $images, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
