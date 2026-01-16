<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Tests\Helper\StrTestHelper;

class ImageIdentifierTest extends TestCase
{
    /**
     * 正常系: 有効なUUIDでインスタンスを作成できること.
     */
    public function test__construct(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $imageIdentifier = new ImageIdentifier($uuid);

        $this->assertSame($uuid, (string) $imageIdentifier);
    }

    /**
     * 異常系: 無効なUUIDで例外が発生すること.
     */
    public function testInvalidUuidThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImageIdentifier('invalid-uuid');
    }

    /**
     * 異常系: 空文字で例外が発生すること.
     */
    public function testEmptyStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImageIdentifier('');
    }
}
