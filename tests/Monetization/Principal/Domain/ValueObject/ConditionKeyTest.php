<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\ConditionKey;

class ConditionKeyTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'RESOURCE_ACCOUNT_ID' => 'resource:accountId',
        ], array_column(ConditionKey::cases(), 'value', 'name'));
    }
}
