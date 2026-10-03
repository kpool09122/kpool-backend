<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;

class CategoryTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'NEWS' => 1,
            'UPDATES' => 2,
            'MAINTENANCE' => 3,
        ], array_column(Category::cases(), 'value', 'name'));
    }
}
