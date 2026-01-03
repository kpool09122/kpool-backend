<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Tests\Helper\StrTestHelper;

class MonetizationAccountIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $identifier = new MonetizationAccountIdentifier($id);
        $this->assertSame($id, (string) $identifier);
    }

    /**
     * 異常系: idが不適切な場合、例外が発生すること
     */
    public function testValidate(): void
    {
        $id = 'invalid-id';
        $this->expectException(InvalidArgumentException::class);
        new MonetizationAccountIdentifier($id);
    }
}
