<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Tests\Helper\StrTestHelper;

class VideoLinkIdentifierTest extends TestCase
{
    /**
     * 正常系: 有効なUUIDでインスタンスを作成できること.
     */
    public function test__construct(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $videoLinkIdentifier = new VideoLinkIdentifier($uuid);

        $this->assertSame($uuid, (string) $videoLinkIdentifier);
    }

    /**
     * 異常系: 無効なUUIDで例外が発生すること.
     */
    public function testInvalidUuidThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VideoLinkIdentifier('invalid-uuid');
    }

    /**
     * 異常系: 空文字で例外が発生すること.
     */
    public function testEmptyStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VideoLinkIdentifier('');
    }
}
