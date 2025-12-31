<?php

declare(strict_types=1);

namespace Monetization\Settlement\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Settlement\Domain\Factory\TransferFactoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccountIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TransferFactoryTest extends TestCase
{
    /**
     * 正常系: 正しく送金インスタンスを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $settlementBatchIdentifier = new SettlementBatchIdentifier(StrTestHelper::generateUuid());
        $settlementAccount = new SettlementAccount(
            new SettlementAccountIdentifier(StrTestHelper::generateUuid()),
            new UserIdentifier(StrTestHelper::generateUuid()),
            'kBank',
            '0124',
            Currency::KRW,
            true,
        );
        $money = new Money(0, Currency::KRW);
        $transferFactory = $this->app->make(TransferFactoryInterface::class);
        $transfer = $transferFactory->create(
            $settlementBatchIdentifier,
            $settlementAccount,
            $money,
        );
        $this->assertSame($settlementBatchIdentifier, $transfer->settlementBatchIdentifier());
        $this->assertSame($settlementAccount, $transfer->settlementAccount());
        $this->assertSame($money, $transfer->amount());
        $this->assertSame(TransferStatus::PENDING, $transfer->status());
        $this->assertNull($transfer->sentAt());
        $this->assertNull($transfer->failedAt());
        $this->assertNull($transfer->failureReason());
    }
}
