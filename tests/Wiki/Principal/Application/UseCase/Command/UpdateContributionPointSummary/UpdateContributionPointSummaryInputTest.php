<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\UpdateContributionPointSummary;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryInput;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;

class UpdateContributionPointSummaryInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $yearMonth = YearMonth::fromDateTime(new DateTimeImmutable('2025-01-15'));

        $input = new UpdateContributionPointSummaryInput($yearMonth);

        $this->assertSame($yearMonth, $input->yearMonth());
    }
}
