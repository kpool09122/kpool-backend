<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\Point;

class PointTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $value = 100;
        $point = new Point($value);
        $this->assertSame($value, $point->value());
    }

    /**
     * 正常系: 編集者のポイント設定が正しいこと
     */
    public function testEditorPoints(): void
    {
        $this->assertSame(10, Point::NEW_EDITOR);
        $this->assertSame(5, Point::UPDATE_EDITOR);
    }

    /**
     * 正常系: 承認者のポイント設定が正しいこと
     */
    public function testApproverPoints(): void
    {
        $this->assertSame(2, Point::NEW_APPROVER);
        $this->assertSame(1, Point::UPDATE_APPROVER);
    }

    /**
     * 正常系: マージ者のポイント設定が正しいこと
     */
    public function testMergerPoints(): void
    {
        $this->assertSame(3, Point::NEW_MERGER);
        $this->assertSame(2, Point::UPDATE_MERGER);
    }

    /**
     * 正常系: クールダウン日数が7日であること
     */
    public function testCooldownDays(): void
    {
        $this->assertSame(7, Point::COOLDOWN_DAYS);
    }

    /**
     * 正常系: 昇格しきい値が50ポイントであること
     */
    public function testPromotionThreshold(): void
    {
        $this->assertSame(50, Point::PROMOTION_THRESHOLD);
    }

    /**
     * 正常系: 上位10%であること
     */
    public function testTopPercentage(): void
    {
        $this->assertSame(0.1, Point::TOP_PERCENTAGE);
    }

    /**
     * 正常系: 最低昇格人数が10人であること
     */
    public function testMinimumPromotedCount(): void
    {
        $this->assertSame(10, Point::MINIMUM_PROMOTED_COUNT);
    }

    /**
     * 正常系: 評価期間が3ヶ月であること
     */
    public function testEvaluationMonths(): void
    {
        $this->assertSame(3, Point::EVALUATION_MONTHS);
    }

    /**
     * 異常系: 負の値で例外がスローされること.
     *
     * @return void
     */
    public function test__constructWithNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Point cannot be negative');

        new Point(-1);
    }

    /**
     * 正常系: 値がPROMOTION_THRESHOLD以上のときtrueを返すこと.
     *
     * @return void
     */
    public function testIsExceedPromotionThresholdTrue(): void
    {
        $atThreshold = new Point(Point::PROMOTION_THRESHOLD);
        $aboveThreshold = new Point(Point::PROMOTION_THRESHOLD + 1);

        $this->assertTrue($atThreshold->isExceedPromotionThreshold());
        $this->assertTrue($aboveThreshold->isExceedPromotionThreshold());
    }

    /**
     * 正常系: 値がPROMOTION_THRESHOLD未満のときfalseを返すこと.
     *
     * @return void
     */
    public function testIsExceedPromotionThresholdFalse(): void
    {
        $belowThreshold = new Point(Point::PROMOTION_THRESHOLD - 1);

        $this->assertFalse($belowThreshold->isExceedPromotionThreshold());
    }
}
