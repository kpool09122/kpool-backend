<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class ApprovalStatusTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'Approved' => 'approved',
            'Pending' => 'pending',
            'Rejected' => 'rejected',
            'UnderReview' => 'under_review',
        ], array_column(ApprovalStatus::cases(), 'value', 'name'));
    }
}
