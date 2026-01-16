<?php

declare(strict_types=1);

namespace Source\Shared\Application\DTO;

use Source\Shared\Domain\ValueObject\ImagePath;

readonly class ImageUploadResult
{
    public function __construct(
        public ImagePath $original,
        public ImagePath $resized,
    ) {
    }
}
