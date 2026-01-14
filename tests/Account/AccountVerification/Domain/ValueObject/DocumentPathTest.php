<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;

class DocumentPathTest extends TestCase
{
    /**
     * 正常系: 有効なパスでインスタンスが生成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $path = '/verifications/documents/test-file.jpg';
        $documentPath = new DocumentPath($path);

        $this->assertSame($path, (string) $documentPath);
    }

    /**
     * 正常系: 最大長ちょうどのパスでインスタンスが生成されること.
     *
     * @return void
     */
    public function testConstructWithMaxLength(): void
    {
        $path = str_repeat('a', DocumentPath::MAX_LENGTH);
        $documentPath = new DocumentPath($path);

        $this->assertSame($path, (string) $documentPath);
    }

    /**
     * 異常系: 空文字列の場合に例外がスローされること.
     *
     * @return void
     */
    public function testThrowsExceptionWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DocumentPath cannot be empty.');

        new DocumentPath('');
    }

    /**
     * 異常系: 空白のみの場合に例外がスローされること.
     *
     * @return void
     */
    public function testThrowsExceptionWhenOnlyWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DocumentPath cannot be empty.');

        new DocumentPath('   ');
    }

    /**
     * 異常系: 最大長を超える場合に例外がスローされること.
     *
     * @return void
     */
    public function testThrowsExceptionWhenExceedsMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DocumentPath cannot exceed ' . DocumentPath::MAX_LENGTH . ' characters.');

        new DocumentPath(str_repeat('a', DocumentPath::MAX_LENGTH + 1));
    }
}
