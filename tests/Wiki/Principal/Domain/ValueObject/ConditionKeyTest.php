<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\ConditionKey;

class ConditionKeyTest extends TestCase
{
    /**
     * 正常系: 全てのケースが定義されていること
     */
    public function test_all_cases_are_defined(): void
    {
        $cases = ConditionKey::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(ConditionKey::RESOURCE_IS_OFFICIAL, $cases);
        $this->assertContains(ConditionKey::RESOURCE_AGENCY_ID, $cases);
        $this->assertContains(ConditionKey::RESOURCE_GROUP_ID, $cases);
        $this->assertContains(ConditionKey::RESOURCE_TALENT_ID, $cases);
    }

    /**
     * 正常系: 各ケースの値が正しいこと
     */
    public function test_case_values(): void
    {
        $this->assertSame('resource:isOfficial', ConditionKey::RESOURCE_IS_OFFICIAL->value);
        $this->assertSame('resource:agencyId', ConditionKey::RESOURCE_AGENCY_ID->value);
        $this->assertSame('resource:groupId', ConditionKey::RESOURCE_GROUP_ID->value);
        $this->assertSame('resource:talentId', ConditionKey::RESOURCE_TALENT_ID->value);
    }
}
