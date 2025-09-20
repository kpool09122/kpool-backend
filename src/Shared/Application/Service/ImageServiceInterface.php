<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service;

use Source\Shared\Domain\ValueObject\ImagePath;

interface ImageServiceInterface
{
    public function upload(string $base64EncodedImage): ImagePath;
}
