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
        $id = StrTestHelper::generateUuid();
        $snapshotIdentifier = new GroupSnapshotIdentifier($id);
        $this->assertSame($id, (string)$snapshotIdentifier);
    }

    /**
     * 異常系: idが不適切な場合、例外が発生すること
     *
     * @return void
     */
    public function testValidate(): void
    {
        $id = 'invalid-id';
        $this->expectException(InvalidArgumentException::class);
        new GroupSnapshotIdentifier($id);
    }
}
