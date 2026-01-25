<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryOutput;

class UpdateContributionPointSummaryOutputTest extends TestCase
{
    /**
     * 正常系: 初期状態で0が返されること.
     *
     * @return void
     */
    public function testInitialState(): void
    {
        $output = new UpdateContributionPointSummaryOutput();

        $this->assertSame(0, $output->updatedCount());
    }

    /**
     * 正常系: setUpdatedCountで更新数が設定され、updatedCountで取得できること.
     *
     * @return void
     */
    public function testSetUpdatedCountAndUpdatedCount(): void
    {
        $output = new UpdateContributionPointSummaryOutput();

        $output->setUpdatedCount(15);

        $this->assertSame(15, $output->updatedCount());
    }
}
