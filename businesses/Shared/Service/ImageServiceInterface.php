<?php

namespace Businesses\Shared\Service;

use Businesses\Member\Domain\ValueObject\ImageLink;

interface ImageServiceInterface
{
    public function upload(string $base64EncodedImage): ImageLink;
}
