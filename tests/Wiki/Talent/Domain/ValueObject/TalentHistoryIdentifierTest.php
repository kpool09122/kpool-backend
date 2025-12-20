<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Talent\Domain\ValueObject\TalentHistoryIdentifier;
use Tests\Helper\StrTestHelper;

class TalentHistoryIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();
        $historyIdentifier = new TalentHistoryIdentifier($ulid);
        $this->assertSame($ulid, (string)$historyIdentifier);
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
        new TalentHistoryIdentifier($ulid);
    }
}
