<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service;

use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Exception\InvalidRemoteImageException;
use Source\Shared\Domain\ValueObject\ImagePath;

interface ImageServiceInterface
{
    /**
     * @param string $base64EncodedImage
     * @return ImagePath
     * @throws InvalidBase64ImageException
     */
    public function upload(string $base64EncodedImage): ImagePath;

    /**
     * @param string $imageUrl
     * @return ImagePath
     * @throws InvalidRemoteImageException
     */
    public function importFromUrl(string $imageUrl): ImagePath;

    public function delete(ImagePath $path): bool;
}
