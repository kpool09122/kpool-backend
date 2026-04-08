<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountMeta;

class PayoutAccountMetaTest extends TestCase
{
    /**
     * 正常系: 全フィールドを指定してインスタンスが作成できること
     */
    public function test__construct(): void
    {
        $accountMeta = new PayoutAccountMeta(
            'MUFG',
            '1234',
            'JP',
            'jpy',
            AccountHolderType::INDIVIDUAL,
        );

        $this->assertSame('MUFG', $accountMeta->bankName());
        $this->assertSame('1234', $accountMeta->last4());
        $this->assertSame('JP', $accountMeta->country());
        $this->assertSame('jpy', $accountMeta->currency());
        $this->assertSame(AccountHolderType::INDIVIDUAL, $accountMeta->accountHolderType());
    }

    /**
     * 正常系: 全フィールドがnullでもインスタンスが作成できること
     */
    public function test__constructWithAllNull(): void
    {
        $accountMeta = new PayoutAccountMeta();

        $this->assertNull($accountMeta->bankName());
        $this->assertNull($accountMeta->last4());
        $this->assertNull($accountMeta->country());
        $this->assertNull($accountMeta->currency());
        $this->assertNull($accountMeta->accountHolderType());
    }

    /**
     * 正常系: 一部フィールドのみ指定してインスタンスが作成できること
     */
    public function test__constructWithPartialFields(): void
    {
        $accountMeta = new PayoutAccountMeta(
            'Mizuho',
            '5678',
        );

        $this->assertSame('Mizuho', $accountMeta->bankName());
        $this->assertSame('5678', $accountMeta->last4());
        $this->assertNull($accountMeta->country());
        $this->assertNull($accountMeta->currency());
        $this->assertNull($accountMeta->accountHolderType());
    }

    /**
     * 正常系: AccountHolderTypeがcompanyの場合も正しく動作すること
     */
    public function test__constructWithCompanyAccountHolderType(): void
    {
        $accountMeta = new PayoutAccountMeta(
            'SMBC',
            '9999',
            'JP',
            'jpy',
            AccountHolderType::COMPANY,
        );

        $this->assertSame(AccountHolderType::COMPANY, $accountMeta->accountHolderType());
    }
}
