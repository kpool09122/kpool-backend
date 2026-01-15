<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service;

use Source\Shared\Application\DTO\ImageUploadResult;
use Source\Shared\Application\Exception\InvalidBase64ImageException;

interface ImageServiceInterface
{
    /**
     * @param string $base64EncodedImage
     * @return ImageUploadResult
     * @throws InvalidBase64ImageException
     */
    public function upload(string $base64EncodedImage): ImageUploadResult;
}
