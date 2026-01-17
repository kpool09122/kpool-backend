<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Settlement\Domain\Repository\SettlementScheduleRepositoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementInterval;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreateSettlementSchedule;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SettlementScheduleRepositoryTest extends TestCase
{
    /**
     * 正常系: 正しくIDに紐づくSettlementScheduleを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementScheduleId = StrTestHelper::generateUuid();

        CreateSettlementSchedule::create($settlementScheduleId, [
            'monetization_account_id' => $monetizationAccountId,
            'interval' => 'monthly',
            'payout_delay_days' => 5,
            'next_closing_date' => '2024-01-31',
        ]);

        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $result = $repository->findById(new SettlementScheduleIdentifier($settlementScheduleId));

        $this->assertNotNull($result);
        $this->assertSame($settlementScheduleId, (string) $result->settlementScheduleIdentifier());
        $this->assertSame($monetizationAccountId, (string) $result->monetizationAccountIdentifier());
        $this->assertSame(SettlementInterval::MONTHLY, $result->interval());
        $this->assertSame(5, $result->paymentDelayDays());
        $this->assertSame('2024-01-31', $result->nextClosingDate()->format('Y-m-d'));
        $this->assertNull($result->threshold());
    }

    /**
     * 正常系: しきい値ベースのSettlementScheduleを正しく取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithThreshold(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementScheduleId = StrTestHelper::generateUuid();

        CreateSettlementSchedule::create($settlementScheduleId, [
            'monetization_account_id' => $monetizationAccountId,
            'interval' => 'threshold',
            'payout_delay_days' => 0,
            'threshold_amount' => 10000,
            'threshold_currency' => 'JPY',
            'next_closing_date' => '2024-01-31',
        ]);

        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $result = $repository->findById(new SettlementScheduleIdentifier($settlementScheduleId));

        $this->assertNotNull($result);
        $this->assertSame(SettlementInterval::THRESHOLD, $result->interval());
        $this->assertSame(0, $result->paymentDelayDays());
        $this->assertNotNull($result->threshold());
        $this->assertSame(10000, $result->threshold()->amount());
        $this->assertSame(Currency::JPY, $result->threshold()->currency());
    }

    /**
     * 正常系: 指定したIDを持つSettlementScheduleが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $result = $repository->findById(new SettlementScheduleIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくMonetizationAccountIdに紐づくSettlementScheduleを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByMonetizationAccountId(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementScheduleId = StrTestHelper::generateUuid();

        CreateSettlementSchedule::create($settlementScheduleId, [
            'monetization_account_id' => $monetizationAccountId,
            'interval' => 'biweekly',
            'payout_delay_days' => 3,
            'next_closing_date' => '2024-02-15',
        ]);

        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $result = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier($monetizationAccountId)
        );

        $this->assertNotNull($result);
        $this->assertSame($settlementScheduleId, (string) $result->settlementScheduleIdentifier());
        $this->assertSame(SettlementInterval::BIWEEKLY, $result->interval());
        $this->assertSame(3, $result->paymentDelayDays());
    }

    /**
     * 正常系: 指定したMonetizationAccountIdを持つSettlementScheduleが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByMonetizationAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $result = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: 期日が到来したスケジュールを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDueSchedules(): void
    {
        $monetizationAccountId1 = StrTestHelper::generateUuid();
        $monetizationAccountId2 = StrTestHelper::generateUuid();
        $monetizationAccountId3 = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId1);
        CreateMonetizationAccount::create($monetizationAccountId2);
        CreateMonetizationAccount::create($monetizationAccountId3);

        $scheduleId1 = StrTestHelper::generateUuid();
        $scheduleId2 = StrTestHelper::generateUuid();
        $scheduleId3 = StrTestHelper::generateUuid();

        // 期日が到来しているスケジュール（2024-01-31以前）
        CreateSettlementSchedule::create($scheduleId1, [
            'monetization_account_id' => $monetizationAccountId1,
            'next_closing_date' => '2024-01-15',
        ]);

        // 期日が到来しているスケジュール（2024-01-31当日）
        CreateSettlementSchedule::create($scheduleId2, [
            'monetization_account_id' => $monetizationAccountId2,
            'next_closing_date' => '2024-01-31',
        ]);

        // 期日が到来していないスケジュール（2024-01-31以降）
        CreateSettlementSchedule::create($scheduleId3, [
            'monetization_account_id' => $monetizationAccountId3,
            'next_closing_date' => '2024-02-15',
        ]);

        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $dueSchedules = $repository->findDueSchedules(new DateTimeImmutable('2024-01-31'));

        $this->assertCount(2, $dueSchedules);

        $dueIds = array_map(fn ($s) => (string) $s->settlementScheduleIdentifier(), $dueSchedules);
        $this->assertContains($scheduleId1, $dueIds);
        $this->assertContains($scheduleId2, $dueIds);
        $this->assertNotContains($scheduleId3, $dueIds);
    }

    /**
     * 正常系: 期日が到来したスケジュールが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDueSchedulesWhenNoDueSchedules(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $scheduleId = StrTestHelper::generateUuid();

        CreateSettlementSchedule::create($scheduleId, [
            'monetization_account_id' => $monetizationAccountId,
            'next_closing_date' => '2024-12-31',
        ]);

        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $dueSchedules = $repository->findDueSchedules(new DateTimeImmutable('2024-01-31'));

        $this->assertCount(0, $dueSchedules);
    }

    /**
     * 正常系: 正しく新規のSettlementScheduleを保存できること（月次）
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewMonthlySchedule(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementScheduleId = StrTestHelper::generateUuid();

        $settlementSchedule = new SettlementSchedule(
            new SettlementScheduleIdentifier($settlementScheduleId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new DateTimeImmutable('2024-01-31'),
            SettlementInterval::MONTHLY,
            5,
        );

        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $repository->save($settlementSchedule);

        $this->assertDatabaseHas('settlement_schedules', [
            'id' => $settlementScheduleId,
            'monetization_account_id' => $monetizationAccountId,
            'interval' => 'monthly',
            'payout_delay_days' => 5,
            'threshold_amount' => null,
            'threshold_currency' => null,
            'next_closing_date' => '2024-01-31',
        ]);
    }

    /**
     * 正常系: 正しく新規のSettlementScheduleを保存できること（しきい値ベース）
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewThresholdSchedule(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementScheduleId = StrTestHelper::generateUuid();

        $settlementSchedule = new SettlementSchedule(
            new SettlementScheduleIdentifier($settlementScheduleId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new DateTimeImmutable('2024-01-31'),
            SettlementInterval::THRESHOLD,
            0,
            new Money(50000, Currency::JPY),
        );

        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $repository->save($settlementSchedule);

        $this->assertDatabaseHas('settlement_schedules', [
            'id' => $settlementScheduleId,
            'monetization_account_id' => $monetizationAccountId,
            'interval' => 'threshold',
            'payout_delay_days' => 0,
            'threshold_amount' => 50000,
            'threshold_currency' => 'JPY',
        ]);
    }

    /**
     * 正常系: 正しく既存のSettlementScheduleを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithExistingScheduleUpdate(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementScheduleId = StrTestHelper::generateUuid();

        $settlementSchedule = new SettlementSchedule(
            new SettlementScheduleIdentifier($settlementScheduleId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new DateTimeImmutable('2024-01-15'),
            SettlementInterval::MONTHLY,
            5,
        );

        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $repository->save($settlementSchedule);

        // advanceを呼び出して次の締め日に進める
        $settlementSchedule->advance();
        $repository->save($settlementSchedule);

        $this->assertDatabaseHas('settlement_schedules', [
            'id' => $settlementScheduleId,
            'next_closing_date' => '2024-02-15',
        ]);

        $result = $repository->findById(new SettlementScheduleIdentifier($settlementScheduleId));
        $this->assertSame('2024-02-15', $result->nextClosingDate()->format('Y-m-d'));
    }

    /**
     * 正常系: 隔週スケジュールを正しく保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindBiweeklySchedule(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementScheduleId = StrTestHelper::generateUuid();

        $settlementSchedule = new SettlementSchedule(
            new SettlementScheduleIdentifier($settlementScheduleId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new DateTimeImmutable('2024-01-15'),
            SettlementInterval::BIWEEKLY,
            3,
        );

        $repository = $this->app->make(SettlementScheduleRepositoryInterface::class);
        $repository->save($settlementSchedule);

        $result = $repository->findById(new SettlementScheduleIdentifier($settlementScheduleId));

        $this->assertNotNull($result);
        $this->assertSame(SettlementInterval::BIWEEKLY, $result->interval());
        $this->assertSame(3, $result->paymentDelayDays());
        $this->assertSame('2024-01-15', $result->nextClosingDate()->format('Y-m-d'));
    }
}
