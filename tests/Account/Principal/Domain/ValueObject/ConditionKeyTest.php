<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\ValueObject\ConditionKey;

class ConditionKeyTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'RESOURCE_ACCOUNT_TYPE' => 'resource:accountType',
            'RESOURCE_ACCOUNT_CATEGORY' => 'resource:accountCategory',
            'AFFILIATION_REQUEST_PAIR_ALLOWED' => 'affiliationRequest:pairAllowed',
            'RESOURCE_DELEGATION_ID' => 'resource:delegationId',
            'RESOURCE_TARGET_ACCOUNT_ID' => 'resource:targetAccountId',
        ], array_column(ConditionKey::cases(), 'value', 'name'));
    }
}
