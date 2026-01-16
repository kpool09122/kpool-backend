<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Storage;
use Source\Shared\Application\DTO\ImageUploadResult;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Infrastructure\Service\ImageService;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    /**
     * 正常系: DIコンテナからImageServiceInterfaceを解決できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCanResolveFromContainer(): void
    {
        $service = $this->app->make(ImageServiceInterface::class);

        $this->assertInstanceOf(ImageService::class, $service);
    }

    /**
     * 正常系: base64エンコードされた画像をアップロードし、ImageUploadResultが返されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidBase64ImageException
     */
    public function testUploadReturnsImageUploadResult(): void
    {
        Storage::fake('s3');

        $imageService = $this->app->make(ImageServiceInterface::class);

        // 1x1の透明なPNG画像
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $result = $imageService->upload($base64Image);

        $this->assertInstanceOf(ImageUploadResult::class, $result);
    }

    /**
     * 正常系: オリジナル画像とリサイズ画像の両方がストレージに保存されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidBase64ImageException
     */
    public function testUploadSavesBothFilesToStorage(): void
    {
        Storage::fake('s3');

        $imageService = $this->app->make(ImageServiceInterface::class);

        // 1x1の透明なPNG画像
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $result = $imageService->upload($base64Image);

        // 両方のファイルがストレージに保存されていることを確認
        Storage::disk('s3')->assertExists((string)$result->original);
        Storage::disk('s3')->assertExists((string)$result->resized);
    }

    /**
     * 正常系: 保存される画像がwebp形式であること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidBase64ImageException
     */
    public function testUploadSavesAsWebp(): void
    {
        Storage::fake('s3');

        $imageService = $this->app->make(ImageServiceInterface::class);

        // 1x1の透明なPNG画像
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $result = $imageService->upload($base64Image);

        $this->assertStringEndsWith('.webp', (string)$result->original);
        $this->assertStringEndsWith('.webp', (string)$result->resized);
    }

    /**
     * 異常系: 不正なbase64文字列の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testUploadThrowsExceptionForInvalidBase64(): void
    {
        Storage::fake('s3');

        $imageService = $this->app->make(ImageServiceInterface::class);

        $this->expectException(InvalidBase64ImageException::class);

        $imageService->upload('not-valid-base64!!!');
    }

    /**
     * 異常系: base64としては有効だが画像データではない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testUploadThrowsExceptionForNonImageData(): void
    {
        Storage::fake('s3');

        $imageService = $this->app->make(ImageServiceInterface::class);

        // "Hello World"をbase64エンコードしたもの（画像ではない）
        $base64Text = base64_encode('Hello World');

        $this->expectException(InvalidBase64ImageException::class);

        $imageService->upload($base64Text);
    }

    /**
     * 正常系: 大きな画像のリサイズ版が1024px以下になること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidBase64ImageException
     */
    public function testUploadResizesLargeImageTo1024px(): void
    {
        Storage::fake('s3');

        $imageService = $this->app->make(ImageServiceInterface::class);

        // 2000x1500の画像を生成
        $largeImage = imagecreatetruecolor(2000, 1500);
        $red = imagecolorallocate($largeImage, 255, 0, 0);
        imagefill($largeImage, 0, 0, $red);

        ob_start();
        imagepng($largeImage);
        $pngData = ob_get_clean();
        imagedestroy($largeImage);

        $base64Image = base64_encode($pngData);

        $result = $imageService->upload($base64Image);

        // リサイズ版のファイルを取得してサイズを確認
        $resizedData = Storage::disk('s3')->get((string)$result->resized);
        $resizedImage = imagecreatefromstring($resizedData);

        $resizedWidth = imagesx($resizedImage);
        $resizedHeight = imagesy($resizedImage);

        imagedestroy($resizedImage);

        // 長辺が1024px以下であることを確認
        $this->assertLessThanOrEqual(1024, $resizedWidth);
        $this->assertLessThanOrEqual(1024, $resizedHeight);
        // 長辺が1024pxであることを確認（アスペクト比維持）
        $this->assertEquals(1024, max($resizedWidth, $resizedHeight));
    }

    /**
     * 正常系: 縦長の大きな画像のリサイズ版が1024px以下になること（高さ基準でリサイズ）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidBase64ImageException
     */
    public function testUploadResizesTallImageTo1024px(): void
    {
        Storage::fake('s3');

        $imageService = $this->app->make(ImageServiceInterface::class);

        // 1500x2000の縦長画像を生成（高さ > 幅）
        $tallImage = imagecreatetruecolor(1500, 2000);
        $green = imagecolorallocate($tallImage, 0, 255, 0);
        imagefill($tallImage, 0, 0, $green);

        ob_start();
        imagepng($tallImage);
        $pngData = ob_get_clean();
        imagedestroy($tallImage);

        $base64Image = base64_encode($pngData);

        $result = $imageService->upload($base64Image);

        // リサイズ版のファイルを取得してサイズを確認
        $resizedData = Storage::disk('s3')->get((string)$result->resized);
        $resizedImage = imagecreatefromstring($resizedData);

        $resizedWidth = imagesx($resizedImage);
        $resizedHeight = imagesy($resizedImage);

        imagedestroy($resizedImage);

        // 長辺（高さ）が1024pxであることを確認
        $this->assertEquals(1024, $resizedHeight);
        // 幅はアスペクト比を維持してリサイズされていることを確認
        $this->assertEquals(768, $resizedWidth);
        // 長辺が1024px以下であることを確認
        $this->assertLessThanOrEqual(1024, $resizedWidth);
        $this->assertLessThanOrEqual(1024, $resizedHeight);
    }

    /**
     * 正常系: 小さな画像はリサイズされないこと.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidBase64ImageException
     */
    public function testUploadDoesNotResizeSmallImage(): void
    {
        Storage::fake('s3');

        $imageService = $this->app->make(ImageServiceInterface::class);

        // 500x300の画像を生成
        $smallImage = imagecreatetruecolor(500, 300);
        $blue = imagecolorallocate($smallImage, 0, 0, 255);
        imagefill($smallImage, 0, 0, $blue);

        ob_start();
        imagepng($smallImage);
        $pngData = ob_get_clean();
        imagedestroy($smallImage);

        $base64Image = base64_encode($pngData);

        $result = $imageService->upload($base64Image);

        // リサイズ版のファイルを取得してサイズを確認
        $resizedData = Storage::disk('s3')->get((string)$result->resized);
        $resizedImage = imagecreatefromstring($resizedData);

        $resizedWidth = imagesx($resizedImage);
        $resizedHeight = imagesy($resizedImage);

        imagedestroy($resizedImage);

        // 元のサイズのままであることを確認
        $this->assertEquals(500, $resizedWidth);
        $this->assertEquals(300, $resizedHeight);
    }
}
