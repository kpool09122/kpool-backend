<?php

namespace Tests\Shared\ValueObject;

use Businesses\Shared\ValueObject\ImageLink;
use PHPUnit\Framework\TestCase;

class ImageLinkTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $link = '/resources/public/images/before.webp';
        $imageLink = new ImageLink($link);
        $this->assertSame($link, (string)$imageLink);
    }
}
