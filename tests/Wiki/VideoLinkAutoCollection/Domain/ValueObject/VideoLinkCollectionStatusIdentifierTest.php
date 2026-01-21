<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLinkAutoCollection\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;
use Tests\Helper\StrTestHelper;

class VideoLinkCollectionStatusIdentifierTest extends TestCase
{
    /**
     * 正常系: 有効なUUIDでインスタンスを作成できること.
     */
    public function test__construct(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $identifier = new VideoLinkCollectionStatusIdentifier($uuid);

        $this->assertSame($uuid, (string) $identifier);
    }

    /**
     * 異常系: 無効なUUIDで例外が発生すること.
     */
    public function testInvalidUuidThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VideoLinkCollectionStatusIdentifier('invalid-uuid');
    }

    /**
     * 異常系: 空文字で例外が発生すること.
     */
    public function testEmptyStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VideoLinkCollectionStatusIdentifier('');
    }
}
