<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;

class ExternalAccountIdTest extends TestCase
{
    /**
     * 正常系: 有効なExternal Account IDでインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $id = 'ba_1234567890abcdef';
        $externalAccountId = new ExternalAccountId($id);
        $this->assertSame($id, (string) $externalAccountId);
    }

    /**
     * 異常系: ba_で始まらない場合、例外が発生すること
     */
    public function testValidateInvalidPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ExternalAccountId('invalid_1234567890');
    }

    /**
     * 異常系: 長さが短すぎる場合、例外が発生すること
     */
    public function testValidateTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ExternalAccountId('ba_123');
    }
}
