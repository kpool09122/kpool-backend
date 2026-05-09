<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListDraftImages;

use Source\Wiki\Image\Application\UseCase\Query\DraftImageReadModel;

class ListDraftImagesOutput implements ListDraftImagesOutputPort
{
    /** @var list<DraftImageReadModel> */
    private array $images = [];

    private ?int $currentPage = null;

    private ?int $lastPage = null;

    private ?int $total = null;

    private ?int $perPage = null;

    /**
     * @param list<DraftImageReadModel> $images
     */
    public function output(array $images, int $currentPage, int $lastPage, int $total, int $perPage): void
    {
        $this->images = $images;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
        $this->perPage = $perPage;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'images' => array_map(static fn (DraftImageReadModel $image): array => $image->toArray(), $this->images),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}
