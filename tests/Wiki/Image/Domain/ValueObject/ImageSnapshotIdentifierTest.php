<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;
use Tests\Helper\StrTestHelper;

class ImageSnapshotIdentifierTest extends TestCase
{
    /**
     * 正常系: 有効なUUIDでインスタンスを作成できること.
     */
    public function test__construct(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $identifier = new ImageSnapshotIdentifier($uuid);

        $this->assertSame($uuid, (string) $identifier);
    }

    /**
     * 異常系: 無効なUUIDで例外が発生すること.
     */
    public function testInvalidUuidThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImageSnapshotIdentifier('invalid-uuid');
    }
}
