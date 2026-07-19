<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests;

use Source\Wiki\Image\Application\UseCase\Query\ImageDeletionRequestListItemReadModel;

interface ListImageDeletionRequestsOutputPort
{
    /**
     * @param list<ImageDeletionRequestListItemReadModel> $images
     */
    public function output(array $images, int $currentPage, int $lastPage, int $total, int $perPage): void;
}
