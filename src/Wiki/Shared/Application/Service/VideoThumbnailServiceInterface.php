<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Application\Service;

use Source\Shared\Domain\ValueObject\ImagePath;

interface VideoThumbnailServiceInterface
{
    public function track(ImagePath $path): void;

    public function releaseUnused(): void;
}
