<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;

class AccountHolderTypeTest extends TestCase
{
    /**
     * 正常系: すべてのタイプが定義されていること
     */
    public function testAllTypesAreDefined(): void
    {
        $this->assertSame('individual', AccountHolderType::INDIVIDUAL->value);
        $this->assertSame('company', AccountHolderType::COMPANY->value);
    }

    /**
     * 正常系: タイプの数が正しいこと
     */
    public function testTypeCount(): void
    {
        $this->assertCount(2, AccountHolderType::cases());
    }

    /**
     * 正常系: 文字列からタイプを取得できること
     */
    public function testFromString(): void
    {
        $this->assertSame(AccountHolderType::INDIVIDUAL, AccountHolderType::from('individual'));
        $this->assertSame(AccountHolderType::COMPANY, AccountHolderType::from('company'));
    }
}
