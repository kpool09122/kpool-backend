<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\WikiSnapshotIdentifier;
use Tests\Helper\StrTestHelper;

class WikiSnapshotIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $wikiSnapshotIdentifier = new WikiSnapshotIdentifier($id);
        $this->assertSame($id, (string)$wikiSnapshotIdentifier);
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
        new WikiSnapshotIdentifier($id);
    }
}
