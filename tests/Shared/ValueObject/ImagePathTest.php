<?php

namespace Tests\Shared\ValueObject;

use Businesses\Shared\ValueObject\ImagePath;
use PHPUnit\Framework\TestCase;

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
