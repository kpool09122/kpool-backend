<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

class SlugTest extends TestCase
{
    /**
     * 正常系: 有効なSlugが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $slug = new Slug('valid-slug');
        $this->assertSame('valid-slug', (string)$slug);
    }

    /**
     * 正常系: 最小長（3文字）のSlugが作成できること.
     *
     * @return void
     */
    public function testMinLength(): void
    {
        $slug = new Slug('abc');
        $this->assertSame('abc', (string)$slug);
    }

    /**
     * 正常系: 最大長（80文字）のSlugが作成できること.
     *
     * @return void
     */
    public function testMaxLength(): void
    {
        $value = str_repeat('a', Slug::MAX_LENGTH);
        $slug = new Slug($value);
        $this->assertSame($value, (string)$slug);
    }

    /**
     * 正常系: ハイフンを含むSlugが作成できること.
     *
     * @return void
     */
    public function testWithHyphen(): void
    {
        $slug = new Slug('valid-slug-name');
        $this->assertSame('valid-slug-name', (string)$slug);
    }

    /**
     * 正常系: 数字を含むSlugが作成できること.
     *
     * @return void
     */
    public function testWithNumbers(): void
    {
        $slug = new Slug('slug123');
        $this->assertSame('slug123', (string)$slug);
    }

    /**
     * 正常系: 数字とハイフンを含むSlugが作成できること.
     *
     * @return void
     */
    public function testWithNumbersAndHyphens(): void
    {
        $slug = new Slug('slug-123-test');
        $this->assertSame('slug-123-test', (string)$slug);
    }

    /**
     * 異常系: 空のSlugで例外がスローされること.
     *
     * @return void
     */
    public function testEmptySlug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be empty');
        new Slug('');
    }

    /**
     * 異常系: 最小長未満で例外がスローされること.
     *
     * @return void
     */
    public function testBelowMinLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must be at least ' . Slug::MIN_LENGTH . ' characters');
        new Slug('ab');
    }

    /**
     * 異常系: 最大長超過で例外がスローされること.
     *
     * @return void
     */
    public function testExceedsMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot exceed ' . Slug::MAX_LENGTH . ' characters');
        new Slug(str_repeat('a', Slug::MAX_LENGTH + 1));
    }

    /**
     * 異常系: 大文字を含む場合に例外がスローされること.
     *
     * @return void
     */
    public function testWithUppercase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must contain only lowercase letters, numbers, and hyphens');
        new Slug('Invalid-Slug');
    }

    /**
     * 異常系: ハイフンで始まる場合に例外がスローされること.
     *
     * @return void
     */
    public function testStartsWithHyphen(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must contain only lowercase letters, numbers, and hyphens');
        new Slug('-invalid-slug');
    }

    /**
     * 異常系: ハイフンで終わる場合に例外がスローされること.
     *
     * @return void
     */
    public function testEndsWithHyphen(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must contain only lowercase letters, numbers, and hyphens');
        new Slug('invalid-slug-');
    }

    /**
     * 異常系: 連続したハイフンを含む場合に例外がスローされること.
     *
     * @return void
     */
    public function testConsecutiveHyphens(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must contain only lowercase letters, numbers, and hyphens');
        new Slug('invalid--slug');
    }

    /**
     * 異常系: スペースを含む場合に例外がスローされること.
     *
     * @return void
     */
    public function testWithSpace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must contain only lowercase letters, numbers, and hyphens');
        new Slug('invalid slug');
    }

    /**
     * 異常系: アンダースコアを含む場合に例外がスローされること.
     *
     * @return void
     */
    public function testWithUnderscore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must contain only lowercase letters, numbers, and hyphens');
        new Slug('invalid_slug');
    }

    /**
     * 異常系: 特殊文字を含む場合に例外がスローされること.
     *
     * @return void
     */
    public function testWithSpecialCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must contain only lowercase letters, numbers, and hyphens');
        new Slug('invalid@slug');
    }

    /**
     * 異常系: 予約語「admin」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordAdmin(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('admin');
    }

    /**
     * 異常系: 予約語「api」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordApi(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('api');
    }

    /**
     * 異常系: 予約語「www」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordWww(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('www');
    }

    /**
     * 異常系: 予約語「null」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('null');
    }

    /**
     * 異常系: 予約語「undefined」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordUndefined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('undefined');
    }

    /**
     * 異常系: 予約語「new」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordNew(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('new');
    }

    /**
     * 異常系: 予約語「edit」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordEdit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('edit');
    }

    /**
     * 異常系: 予約語「delete」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordDelete(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('delete');
    }

    /**
     * 異常系: 予約語「create」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordCreate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('create');
    }

    /**
     * 異常系: 予約語「update」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordUpdate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('update');
    }

    /**
     * 異常系: 予約語「settings」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordSettings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('settings');
    }

    /**
     * 異常系: 予約語「search」の場合に例外がスローされること.
     *
     * @return void
     */
    public function testReservedWordSearch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be a reserved word');
        new Slug('search');
    }

    /**
     * 正常系: 予約語を含むが完全一致ではないSlugが作成できること.
     *
     * @return void
     */
    public function testReservedWordAsPartOfSlug(): void
    {
        $slug = new Slug('admin-page');
        $this->assertSame('admin-page', (string)$slug);

        $slug2 = new Slug('my-api-test');
        $this->assertSame('my-api-test', (string)$slug2);
    }
}
