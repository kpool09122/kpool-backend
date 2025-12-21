<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupSource;

class AutomaticDraftGroupSourceTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     */
    public function test__construct(): void
    {
        $source = 'news::source-id';
        $automaticDraftGroupSource = new AutomaticDraftGroupSource($source);

        $this->assertSame($source, (string) $automaticDraftGroupSource);
    }

    /**
     * 異常系: 空文字の時、例外がスローされること.
     */
    public function testWithEmptyValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutomaticDraftGroupSource('');
    }

    /**
     * 異常系: 空文字の時、例外がスローされること.
     */
    public function testWithOnlySpaceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutomaticDraftGroupSource('    ');
    }

    /**
     * 異常系: 最大文字数を超えた場合、例外がスローされること.
     */
    public function testWithTooLongValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutomaticDraftGroupSource(str_repeat('a', AutomaticDraftGroupSource::MAX_LENGTH + 1));
    }
}
