<?php

declare(strict_types=1);

namespace Tests\Shared\Application\DTO;

use PHPUnit\Framework\TestCase;
use Source\Shared\Application\DTO\ImageUploadResult;
use Source\Shared\Domain\ValueObject\ImagePath;

class ImageUploadResultTest extends TestCase
{
    /**
     * 正常系: 画像アップロード結果が単一の保存パスを保持すること.
     *
     * @return void
     */
    public function testCreateImageUploadResult(): void
    {
        $path = new ImagePath('images/normalized.webp');

        $result = new ImageUploadResult($path);

        $this->assertInstanceOf(ImageUploadResult::class, $result);
        $this->assertSame($path, $result->path);
    }
}
