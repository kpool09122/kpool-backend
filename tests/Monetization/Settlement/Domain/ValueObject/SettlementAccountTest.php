<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccountIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;

class SettlementAccountTest extends TestCase
{
    /**
     * 正常系: 口座情報を保持できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $accountId = new SettlementAccountIdentifier(StrTestHelper::generateUuid());
        $ownerId = new UserIdentifier(StrTestHelper::generateUuid());

        $account = new SettlementAccount(
            $accountId,
            $ownerId,
            'KBank',
            '1234',
            Currency::JPY,
            true
        );

        $this->assertSame($accountId, $account->settlementAccountIdentifier());
        $this->assertSame($ownerId, $account->ownerIdentifier());
        $this->assertSame('KBank', $account->bankName());
        $this->assertSame('1234', $account->accountNumberLast4());
        $this->assertSame(Currency::JPY, $account->currency());
        $this->assertTrue($account->isVerified());
    }

    /**
     * 異常系: 口座番号の末尾4桁が数字以外の場合例外となること.
     *
     * @return void
     */
    public function testInvalidAccountNumberLast4(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettlementAccount(
            new SettlementAccountIdentifier(StrTestHelper::generateUuid()),
            new UserIdentifier(StrTestHelper::generateUuid()),
            'KBank',
            '12A4',
            Currency::USD,
            true
        );
    }

    /**
     * 異常系: 銀行名が空の場合は例外となること.
     *
     * @return void
     */
    public function testEmptyBankName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettlementAccount(
            new SettlementAccountIdentifier(StrTestHelper::generateUuid()),
            new UserIdentifier(StrTestHelper::generateUuid()),
            '',
            '0000',
            Currency::KRW,
            false
        );
    }
}
