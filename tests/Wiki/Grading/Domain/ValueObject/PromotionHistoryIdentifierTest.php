<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Grading\Domain\ValueObject\PromotionHistoryIdentifier;
use Tests\Helper\StrTestHelper;

class PromotionHistoryIdentifierTest extends TestCase
{
    /**
     * 正常系: 有効なUUIDでインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $promotionHistoryIdentifier = new PromotionHistoryIdentifier($id);
        $this->assertSame($id, (string) $promotionHistoryIdentifier);
    }

    /**
     * 異常系: 不正な値の場合、例外が発生すること
     */
    public function testValidate(): void
    {
        $id = 'invalid-id';
        $this->expectException(InvalidArgumentException::class);
        new PromotionHistoryIdentifier($id);
    }
}
