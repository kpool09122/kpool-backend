<?php

namespace Tests\Member\Domain\ValueObject;

use Businesses\Member\Domain\ValueObject\ImageLink;
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
