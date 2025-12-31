<?php

declare(strict_types=1);

namespace Monetization\Settlement\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Settlement\Domain\Factory\SettlementBatchFactoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccountIdentifier;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SettlementBatchFactoryTest extends TestCase
{
    /**
     * 正常系: 正しくSettlementBatchインスタンスが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $account = new SettlementAccount(
            new SettlementAccountIdentifier(StrTestHelper::generateUuid()),
            new UserIdentifier(StrTestHelper::generateUuid()),
            'KBank',
            '1234',
            Currency::USD,
            true
        );
        $start = new DateTimeImmutable('now');
        $end = new DateTimeImmutable('now');

        $factory = $this->app->make(SettlementBatchFactoryInterface::class);
        $batch = $factory->create($account, $start, $end);
        $this->assertTrue(UuidValidator::isValid((string)$batch->settlementBatchIdentifier()));
        $this->assertSame($account, $batch->settlementAccount());
        $this->assertSame($start, $batch->periodStart());
        $this->assertSame($end, $batch->periodEnd());
    }
}
