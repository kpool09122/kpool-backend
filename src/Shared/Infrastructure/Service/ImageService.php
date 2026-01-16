<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Service;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Source\Shared\Application\DTO\ImageUploadResult;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\ImagePath;

class ImageService implements ImageServiceInterface
{
    private const int MAX_RESIZED_DIMENSION = 1024;

    /**
     * @param string $base64EncodedImage
     * @return ImageUploadResult
     * @throws InvalidBase64ImageException
     */
    public function upload(string $base64EncodedImage): ImageUploadResult
    {
        $imageData = base64_decode($base64EncodedImage, true);

        if ($imageData === false) {
            throw new InvalidBase64ImageException();
        }

        $gdImage = @imagecreatefromstring($imageData);
        if ($gdImage === false) {
            throw new InvalidBase64ImageException();
        }

        $baseFileName = Str::uuid()->toString();

        // オリジナル画像をwebpで保存
        $originalPath = $this->saveAsWebp($gdImage, $baseFileName . '_original.webp');

        // リサイズ版を作成して保存
        $resizedImage = $this->resizeImage($gdImage);
        $resizedPath = $this->saveAsWebp($resizedImage, $baseFileName . '_resized.webp');

        // リソース解放
        imagedestroy($gdImage);
        if ($resizedImage !== $gdImage) {
            imagedestroy($resizedImage);
        }

        return new ImageUploadResult(
            new ImagePath($originalPath),
            new ImagePath($resizedPath),
        );
    }

    /**
     * @param GdImage $image
     * @param string $fileName
     * @return string
     */
    private function saveAsWebp(GdImage $image, string $fileName): string
    {
        ob_start();
        imagewebp($image);
        $webpData = ob_get_clean();

        Storage::disk('s3')->put($fileName, $webpData);

        return $fileName;
    }

    /**
     * @param GdImage $image
     * @return GdImage
     */
    private function resizeImage(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // 既に指定サイズ以下の場合はそのまま返す
        if ($width <= self::MAX_RESIZED_DIMENSION && $height <= self::MAX_RESIZED_DIMENSION) {
            return $image;
        }

        // アスペクト比を維持してリサイズ
        if ($width > $height) {
            $newWidth = self::MAX_RESIZED_DIMENSION;
            $newHeight = (int)floor($height * (self::MAX_RESIZED_DIMENSION / $width));
        } else {
            $newHeight = self::MAX_RESIZED_DIMENSION;
            $newWidth = (int)floor($width * (self::MAX_RESIZED_DIMENSION / $height));
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // 透過を維持
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height,
        );

        return $resized;
    }
}
