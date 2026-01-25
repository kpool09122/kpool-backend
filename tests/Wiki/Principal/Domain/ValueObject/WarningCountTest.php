<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\WarningCount;

class WarningCountTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $value = 1;
        $warningCount = new WarningCount($value);
        $this->assertSame($value, $warningCount->value());
    }

    /**
     * 正常系: 0でインスタンスを作成できること.
     *
     * @return void
     */
    public function test__constructWithZero(): void
    {
        $warningCount = new WarningCount(0);
        $this->assertSame(0, $warningCount->value());
    }

    /**
     * 異常系: 負の値で例外がスローされること.
     *
     * @return void
     */
    public function test__constructWithNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Warning count must be greater than 0');

        new WarningCount(-1);
    }

    /**
     * 正常系: incrementで値が1増えた新しいインスタンスが返されること.
     *
     * @return void
     */
    public function testIncrement(): void
    {
        $warningCount = new WarningCount(1);
        $incremented = $warningCount->increment();

        $this->assertSame(2, $incremented->value());
        $this->assertSame(1, $warningCount->value());
    }

    /**
     * 正常系: resetで値が0の新しいインスタンスが返されること.
     *
     * @return void
     */
    public function testReset(): void
    {
        $warningCount = new WarningCount(2);
        $reset = $warningCount->reset();

        $this->assertSame(0, $reset->value());
        $this->assertSame(2, $warningCount->value());
    }

    /**
     * 正常系: 値がWARNING_THRESHOLDと等しいときtrueを返すこと.
     *
     * @return void
     */
    public function testIsReachedWarningThresholdTrue(): void
    {
        $warningCount = new WarningCount(WarningCount::WARNING_THRESHOLD);

        $this->assertTrue($warningCount->isReachedWarningThreshold());
    }

    /**
     * 正常系: 値がWARNING_THRESHOLDと異なるときfalseを返すこと.
     *
     * @return void
     */
    public function testIsReachedWarningThresholdFalse(): void
    {
        $belowThreshold = new WarningCount(WarningCount::WARNING_THRESHOLD - 1);
        $aboveThreshold = new WarningCount(WarningCount::WARNING_THRESHOLD + 1);

        $this->assertFalse($belowThreshold->isReachedWarningThreshold());
        $this->assertFalse($aboveThreshold->isReachedWarningThreshold());
    }

    /**
     * 正常系: 値がDEMOTION_THRESHOLD以上のときtrueを返すこと.
     *
     * @return void
     */
    public function testIsExceedDemotionThresholdTrue(): void
    {
        $atThreshold = new WarningCount(WarningCount::DEMOTION_THRESHOLD);
        $aboveThreshold = new WarningCount(WarningCount::DEMOTION_THRESHOLD + 1);

        $this->assertTrue($atThreshold->isExceedDemotionThreshold());
        $this->assertTrue($aboveThreshold->isExceedDemotionThreshold());
    }

    /**
     * 正常系: 値がDEMOTION_THRESHOLD未満のときfalseを返すこと.
     *
     * @return void
     */
    public function testIsExceedDemotionThresholdFalse(): void
    {
        $belowThreshold = new WarningCount(WarningCount::DEMOTION_THRESHOLD - 1);

        $this->assertFalse($belowThreshold->isExceedDemotionThreshold());
    }

    /**
     * 正常系: 警告しきい値が2であること.
     *
     * @return void
     */
    public function testWarningThreshold(): void
    {
        $this->assertSame(2, WarningCount::WARNING_THRESHOLD);
    }

    /**
     * 正常系: 降格しきい値が3であること.
     *
     * @return void
     */
    public function testDemotionThreshold(): void
    {
        $this->assertSame(3, WarningCount::DEMOTION_THRESHOLD);
    }
}
