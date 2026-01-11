<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\DeletionBlockReason;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;

class DeletionReadinessChecklistTest extends TestCase
{
    /**
     * 正常系: すべての削除前提条件が満たされている場合、ブロッカーが存在しないこと.
     *
     * @return void
     */
    public function testReadyChecklist(): void
    {
        $checklist = DeletionReadinessChecklist::ready();

        $this->assertTrue($checklist->isReady());
        $this->assertSame([], $checklist->blockers());
    }

    /**
     * 異常系: 未クリアの要素がある場合、ブロッカーが列挙されること.
     *
     * @return void
     */
    public function testEnumeratesBlockers(): void
    {
        $checklist = DeletionReadinessChecklist::fromReasons(
            DeletionBlockReason::UNPAID_INVOICES,
            DeletionBlockReason::EXTERNAL_INTEGRATIONS_ACTIVE,
            DeletionBlockReason::OPEN_TICKETS,
        );

        $this->assertFalse($checklist->isReady());
        $this->assertEquals(
            [
                DeletionBlockReason::UNPAID_INVOICES,
                DeletionBlockReason::EXTERNAL_INTEGRATIONS_ACTIVE,
                DeletionBlockReason::OPEN_TICKETS,
            ],
            $checklist->blockers()
        );
    }
}
