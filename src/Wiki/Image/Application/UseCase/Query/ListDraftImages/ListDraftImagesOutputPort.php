<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListDraftImages;

use Source\Wiki\Image\Application\UseCase\Query\DraftImageReadModel;

interface ListDraftImagesOutputPort
{
    /**
     * @param list<DraftImageReadModel> $images
     */
    public function output(array $images, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
