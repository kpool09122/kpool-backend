<?php

namespace Businesses\Shared\Service;

use Businesses\Shared\ValueObject\ImageLink;

interface ImageServiceInterface
{
    public function upload(string $base64EncodedImage): ImageLink;
}
