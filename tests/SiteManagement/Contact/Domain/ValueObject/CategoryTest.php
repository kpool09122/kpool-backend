<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;

class CategoryTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'SUGGESTIONS' => 1,
            'ISSUES' => 2,
            'CORRECTION' => 3,
            'OTHERS' => 99,
        ], array_column(Category::cases(), 'value', 'name'));
    }
}
