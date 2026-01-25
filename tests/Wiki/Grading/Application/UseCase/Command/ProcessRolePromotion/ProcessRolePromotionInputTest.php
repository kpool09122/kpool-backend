<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion;

use DateTimeImmutable;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionInput;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Tests\TestCase;

class ProcessRolePromotionInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $yearMonth = YearMonth::fromDateTime(new DateTimeImmutable('2025-01-15'));

        $input = new ProcessRolePromotionInput($yearMonth);

        $this->assertSame($yearMonth, $input->yearMonth());
    }
}
