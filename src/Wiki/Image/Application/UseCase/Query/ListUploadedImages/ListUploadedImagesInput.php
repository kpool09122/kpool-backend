<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages;

readonly class ListUploadedImagesInput implements ListUploadedImagesInputPort
{
    public function __construct(
        private string $wikiIdentifier,
        private ?int $perPage = null,
    ) {
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }

    public function wikiIdentifier(): string
    {
        return $this->wikiIdentifier;
    }
}
