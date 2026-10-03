<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Grading\Domain\ValueObject\ContributorType;

class ContributorTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'EDITOR' => 'editor',
            'APPROVER' => 'approver',
            'MERGER' => 'merger',
        ], array_column(ContributorType::cases(), 'value', 'name'));
    }
}
