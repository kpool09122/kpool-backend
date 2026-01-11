<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;

class ConditionValueTest extends TestCase
{
    /**
     * 正常系: 全てのケースが定義されていること
     */
    public function test_all_cases_are_defined(): void
    {
        $cases = ConditionValue::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(ConditionValue::PRINCIPAL_AGENCY_ID, $cases);
        $this->assertContains(ConditionValue::PRINCIPAL_WIKI_GROUP_IDS, $cases);
        $this->assertContains(ConditionValue::PRINCIPAL_TALENT_IDS, $cases);
    }

    /**
     * 正常系: 各ケースの値が正しいこと
     */
    public function test_case_values(): void
    {
        $this->assertSame('${principal.agencyId}', ConditionValue::PRINCIPAL_AGENCY_ID->value);
        $this->assertSame('${principal.wikiGroupIds}', ConditionValue::PRINCIPAL_WIKI_GROUP_IDS->value);
        $this->assertSame('${principal.talentIds}', ConditionValue::PRINCIPAL_TALENT_IDS->value);
    }
}
