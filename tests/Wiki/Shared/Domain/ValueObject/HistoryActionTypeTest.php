<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;

class HistoryActionTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'DraftStatusChange' => 'draft_status_change',
            'Publish' => 'publish',
            'Rollback' => 'rollback',
        ], array_column(HistoryActionType::cases(), 'value', 'name'));
    }
}
