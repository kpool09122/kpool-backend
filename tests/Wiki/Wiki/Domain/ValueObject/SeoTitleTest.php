<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;
use Tests\TestCase;

class SeoTitleTest extends TestCase
{
    public function testCreatesSeoTitle(): void
    {
        $title = new SeoTitle(str_repeat('あ', SeoTitle::MAX_LENGTH));

        $this->assertSame(str_repeat('あ', SeoTitle::MAX_LENGTH), (string) $title);
    }

    public function testThrowsInvalidArgumentExceptionWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SeoTitle('');
    }

    public function testThrowsInvalidArgumentExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SeoTitle(str_repeat('あ', SeoTitle::MAX_LENGTH + 1));
    }
}
