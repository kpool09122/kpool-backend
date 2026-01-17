<?php

declare(strict_types=1);

namespace Monetization\Settlement\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Factory\SettlementBatchFactoryInterface;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Currency;
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
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $currency = Currency::USD;
        $start = new DateTimeImmutable('now');
        $end = new DateTimeImmutable('now');

        $factory = $this->app->make(SettlementBatchFactoryInterface::class);
        $batch = $factory->create($monetizationAccountId, $currency, $start, $end);
        $this->assertTrue(UuidValidator::isValid((string)$batch->settlementBatchIdentifier()));
        $this->assertSame((string)$monetizationAccountId, (string)$batch->monetizationAccountIdentifier());
        $this->assertSame($currency, $batch->currency());
        $this->assertSame($start, $batch->periodStart());
        $this->assertSame($end, $batch->periodEnd());
    }
}
