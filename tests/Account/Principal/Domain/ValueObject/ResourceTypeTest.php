<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\ValueObject\ResourceType;

class ResourceTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'ACCOUNT' => 'account',
        ], array_column(ResourceType::cases(), 'value', 'name'));
    }
}
