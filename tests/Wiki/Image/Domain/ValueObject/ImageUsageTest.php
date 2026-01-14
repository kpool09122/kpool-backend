<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;

class ImageUsageTest extends TestCase
{
    /**
     * 正常系: PROFILEが正しく定義されていること.
     */
    public function testProfileCase(): void
    {
        $usage = ImageUsage::PROFILE;

        $this->assertSame('profile', $usage->value);
    }

    /**
     * 正常系: COVERが正しく定義されていること.
     */
    public function testCoverCase(): void
    {
        $usage = ImageUsage::COVER;

        $this->assertSame('cover', $usage->value);
    }

    /**
     * 正常系: LOGOが正しく定義されていること.
     */
    public function testLogoCase(): void
    {
        $usage = ImageUsage::LOGO;

        $this->assertSame('logo', $usage->value);
    }

    /**
     * 正常系: ADDITIONALが正しく定義されていること.
     */
    public function testAdditionalCase(): void
    {
        $usage = ImageUsage::ADDITIONAL;

        $this->assertSame('additional', $usage->value);
    }

    /**
     * 正常系: fromメソッドで文字列からenumを生成できること.
     */
    public function testFromString(): void
    {
        $this->assertSame(ImageUsage::PROFILE, ImageUsage::from('profile'));
        $this->assertSame(ImageUsage::COVER, ImageUsage::from('cover'));
        $this->assertSame(ImageUsage::LOGO, ImageUsage::from('logo'));
        $this->assertSame(ImageUsage::ADDITIONAL, ImageUsage::from('additional'));
    }
}
