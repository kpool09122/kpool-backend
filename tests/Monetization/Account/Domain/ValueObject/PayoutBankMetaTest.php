<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\PayoutBankMeta;

class PayoutBankMetaTest extends TestCase
{
    /**
     * 正常系: 全フィールドを指定してインスタンスが作成できること
     */
    public function test__construct(): void
    {
        $bankMeta = new PayoutBankMeta(
            'MUFG',
            '1234',
            'JP',
            'jpy',
            AccountHolderType::INDIVIDUAL,
        );

        $this->assertSame('MUFG', $bankMeta->bankName());
        $this->assertSame('1234', $bankMeta->last4());
        $this->assertSame('JP', $bankMeta->country());
        $this->assertSame('jpy', $bankMeta->currency());
        $this->assertSame(AccountHolderType::INDIVIDUAL, $bankMeta->accountHolderType());
    }

    /**
     * 正常系: 全フィールドがnullでもインスタンスが作成できること
     */
    public function test__constructWithAllNull(): void
    {
        $bankMeta = new PayoutBankMeta();

        $this->assertNull($bankMeta->bankName());
        $this->assertNull($bankMeta->last4());
        $this->assertNull($bankMeta->country());
        $this->assertNull($bankMeta->currency());
        $this->assertNull($bankMeta->accountHolderType());
    }

    /**
     * 正常系: 一部フィールドのみ指定してインスタンスが作成できること
     */
    public function test__constructWithPartialFields(): void
    {
        $bankMeta = new PayoutBankMeta(
            'Mizuho',
            '5678',
        );

        $this->assertSame('Mizuho', $bankMeta->bankName());
        $this->assertSame('5678', $bankMeta->last4());
        $this->assertNull($bankMeta->country());
        $this->assertNull($bankMeta->currency());
        $this->assertNull($bankMeta->accountHolderType());
    }

    /**
     * 正常系: AccountHolderTypeがcompanyの場合も正しく動作すること
     */
    public function test__constructWithCompanyAccountHolderType(): void
    {
        $bankMeta = new PayoutBankMeta(
            'SMBC',
            '9999',
            'JP',
            'jpy',
            AccountHolderType::COMPANY,
        );

        $this->assertSame(AccountHolderType::COMPANY, $bankMeta->accountHolderType());
    }
}
