<?php

namespace Businesses\Shared\Service;

use Businesses\Shared\ValueObject\ImagePath;

interface ImageServiceInterface
{
    public function upload(string $base64EncodedImage): ImagePath;
}
