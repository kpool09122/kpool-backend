<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;

class ImagePathTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $path = '/resources/public/images/before.webp';
        $imagePath = new ImagePath($path);
        $this->assertSame($path, (string)$imagePath);
    }
}
