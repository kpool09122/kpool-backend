<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\SocialProvider;

class SocialProviderTest extends TestCase
{
    /**
     * 正常系: 正しく文字列からインスタンスが作成されること.
     *
     * @return void
     */
    public function testFromString(): void
    {
        $this->assertSame(SocialProvider::GOOGLE, SocialProvider::fromString('google'));
        $this->assertSame(SocialProvider::LINE, SocialProvider::fromString('line'));
        $this->assertSame(SocialProvider::INSTAGRAM, SocialProvider::fromString('instagram'));
    }

    /**
     * 異常系: 不正な文字列が渡された場合は例外がスローされること.
     *
     * @return void
     */
    public function testThrowsExceptionWhenInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SocialProvider::fromString('twitter');
    }
}
