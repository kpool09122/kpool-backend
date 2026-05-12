<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface ListUploadedImagesInputPort
{
    public function perPage(): int;

    public function translationSetIdentifier(): TranslationSetIdentifier;
}
