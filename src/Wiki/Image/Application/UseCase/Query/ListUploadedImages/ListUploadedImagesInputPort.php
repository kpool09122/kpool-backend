<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages;

interface ListUploadedImagesInputPort
{
    public function perPage(): int;

    public function wikiIdentifier(): string;
}
