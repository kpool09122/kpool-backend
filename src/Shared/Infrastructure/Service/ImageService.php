<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Service;

use GdImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToWriteFile;
use Psr\Log\LoggerInterface;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Exception\InvalidRemoteImageException;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\ImagePath;
use Throwable;
use UnexpectedValueException;

class ImageService implements ImageServiceInterface
{
    private const int MAX_RESIZED_DIMENSION = 1024;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @param string $base64EncodedImage
     * @return ImagePath
     * @throws InvalidBase64ImageException
     */
    public function upload(string $base64EncodedImage): ImagePath
    {
        $imageData = base64_decode($base64EncodedImage, true);

        if ($imageData === false) {
            throw new InvalidBase64ImageException();
        }

        $gdImage = @imagecreatefromstring($imageData);
        if ($gdImage === false) {
            throw new InvalidBase64ImageException();
        }

        return $this->storeImage($gdImage);
    }

    public function importFromUrl(string $imageUrl): ImagePath
    {
        try {
            $response = Http::timeout(5)->get($imageUrl);
        } catch (Throwable $e) {
            throw new InvalidRemoteImageException('Failed to download remote image.', previous: $e);
        }

        if (! $response->successful()) {
            throw new InvalidRemoteImageException('Failed to download remote image.');
        }

        $imageData = $response->body();
        if ($imageData === '') {
            throw new InvalidRemoteImageException('Remote image is empty.');
        }

        $gdImage = @imagecreatefromstring($imageData);
        if ($gdImage === false) {
            throw new InvalidRemoteImageException('Remote URL did not contain a valid image.');
        }

        return $this->storeImage($gdImage);
    }

    public function delete(ImagePath $path): bool
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => $this->deleteFile($path));

            return true;
        }

        return $this->deleteFile($path);
    }

    private function deleteFile(ImagePath $path): bool
    {
        try {
            $deleted = Storage::disk(config()->string('filesystems.image_disk', 'public'))->delete((string) $path);
            if (! $deleted) {
                $this->logger->warning('Failed to delete image.', ['imagePath' => (string) $path]);
            }

            return $deleted;
        } catch (Throwable $exception) {
            $this->logger->warning('Failed to delete image.', ['imagePath' => (string) $path, 'exception' => $exception]);

            return false;
        }
    }

    private function storeImage(GdImage $gdImage): ImagePath
    {
        $normalizedImage = $this->resizeImage($gdImage);
        $path = $this->saveAsWebp($normalizedImage, 'images/' . Str::uuid() . '.webp');

        return new ImagePath($path);
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

        if (! Storage::disk(config()->string('filesystems.image_disk', 'public'))->put($fileName, $webpData)) {
            throw UnableToWriteFile::atLocation($fileName);
        }

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

        if ($newWidth < 1 || $newHeight < 1) {
            throw new UnexpectedValueException('Resized image dimensions must be positive.');
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
