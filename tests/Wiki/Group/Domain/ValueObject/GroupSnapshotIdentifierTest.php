<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Group\Domain\ValueObject\GroupSnapshotIdentifier;
use Tests\Helper\StrTestHelper;

class GroupSnapshotIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();
        $snapshotIdentifier = new GroupSnapshotIdentifier($ulid);
        $this->assertSame($ulid, (string)$snapshotIdentifier);
    }

    /**
     * 異常系: ulidが不適切な場合、例外が発生すること
     *
     * @return void
     */
    public function testValidate(): void
    {
        $ulid = 'invalid-ulid';
        $this->expectException(InvalidArgumentException::class);
        new GroupSnapshotIdentifier($ulid);
    }
}
