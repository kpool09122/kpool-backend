<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Tests\Helper\StrTestHelper;

class WikiHistoryIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $wikiHistoryIdentifier = new WikiHistoryIdentifier($id);
        $this->assertSame($id, (string)$wikiHistoryIdentifier);
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
        new WikiHistoryIdentifier($id);
    }
}
