<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;

readonly class ListUploadedImagesInput implements ListUploadedImagesInputPort
{
    public function __construct(
        private TranslationSetIdentifier $translationSetIdentifier,
        private ?int $perPage = null,
    ) {
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }
}
