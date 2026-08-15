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

        $this->assertCount(6, $cases);
        $this->assertContains(ConditionValue::PRINCIPAL_AGENCY_WIKI_IDENTIFIERS, $cases);
        $this->assertContains(ConditionValue::PRINCIPAL_GROUP_WIKI_IDENTIFIERS, $cases);
        $this->assertContains(ConditionValue::PRINCIPAL_TALENT_GROUP_WIKI_IDENTIFIERS, $cases);
        $this->assertContains(ConditionValue::PRINCIPAL_TALENT_WIKI_IDENTIFIERS, $cases);
        $this->assertContains(ConditionValue::PRINCIPAL_AFFILIATED_TALENT_WIKI_IDENTIFIERS, $cases);
        $this->assertContains(ConditionValue::PRINCIPAL_ID, $cases);
    }

    /**
     * 正常系: 各ケースの値が正しいこと
     */
    public function test_case_values(): void
    {
        $this->assertSame('${principal.agencyWikiIdentifiers}', ConditionValue::PRINCIPAL_AGENCY_WIKI_IDENTIFIERS->value);
        $this->assertSame('${principal.groupWikiIdentifiers}', ConditionValue::PRINCIPAL_GROUP_WIKI_IDENTIFIERS->value);
        $this->assertSame('${principal.talentGroupWikiIdentifiers}', ConditionValue::PRINCIPAL_TALENT_GROUP_WIKI_IDENTIFIERS->value);
        $this->assertSame('${principal.talentWikiIdentifiers}', ConditionValue::PRINCIPAL_TALENT_WIKI_IDENTIFIERS->value);
        $this->assertSame('${principal.affiliatedTalentWikiIdentifiers}', ConditionValue::PRINCIPAL_AFFILIATED_TALENT_WIKI_IDENTIFIERS->value);
        $this->assertSame('${principal.id}', ConditionValue::PRINCIPAL_ID->value);
    }
}
