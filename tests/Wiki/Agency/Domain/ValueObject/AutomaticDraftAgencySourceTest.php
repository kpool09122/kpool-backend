<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencySource;

class AutomaticDraftAgencySourceTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $source = 'news::source-id';
        $automaticDraftAgencySource = new AutomaticDraftAgencySource($source);

        $this->assertSame($source, (string) $automaticDraftAgencySource);
    }

    /**
     * 異常系: 空文字の時、例外がスローされること.
     *
     * @return void
     */
    public function testWithEmptyValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutomaticDraftAgencySource('');
    }

    /**
     * 異常系: 空白時、例外がスローされること.
     *
     * @return void
     */
    public function testWithOnlySpaceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutomaticDraftAgencySource('    ');
    }

    /**
     * 異常系: 最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testWithTooLongValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutomaticDraftAgencySource(str_repeat('a', AutomaticDraftAgencySource::MAX_LENGTH + 1));
    }
}
