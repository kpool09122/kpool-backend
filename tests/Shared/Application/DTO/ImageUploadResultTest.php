<?php

declare(strict_types=1);

namespace Tests\Shared\Application\DTO;

use PHPUnit\Framework\TestCase;
use Source\Shared\Application\DTO\ImageUploadResult;
use Source\Shared\Domain\ValueObject\ImagePath;

class ImageUploadResultTest extends TestCase
{
    /**
     * 正常系: インスタンスが作成でき、各プロパティに正しくアクセスできること.
     *
     * @return void
     */
    public function testConstructAndAccessProperties(): void
    {
        $original = new ImagePath('images/original.webp');
        $resized = new ImagePath('images/resized.webp');

        $result = new ImageUploadResult($original, $resized);

        $this->assertInstanceOf(ImageUploadResult::class, $result);
        $this->assertSame($original, $result->original);
        $this->assertSame($resized, $result->resized);
    }
}
