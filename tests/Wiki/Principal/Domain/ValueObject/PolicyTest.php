<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\Policy;
use Source\Wiki\Principal\Domain\ValueObject\ScopeCondition;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    /**
     * 正常系: FULL_ACCESSは全アクション・全リソースに対してALLOWを返すこと.
     *
     * @return void
     */
    public function testFullAccessStatements(): void
    {
        $statements = Policy::FULL_ACCESS->statements();

        $this->assertCount(1, $statements);

        $statement = $statements[0];
        $this->assertSame(Effect::ALLOW, $statement->effect());
        $this->assertSame(Action::cases(), $statement->actions());
        $this->assertSame(ResourceType::cases(), $statement->resourceTypes());
        $this->assertSame(ScopeCondition::NONE, $statement->scopeCondition());
    }

    /**
     * 正常系: BASIC_EDITINGはCREATE, EDIT, SUBMITを全リソースに対してALLOWを返すこと.
     *
     * @return void
     */
    public function testBasicEditingStatements(): void
    {
        $statements = Policy::BASIC_EDITING->statements();

        $this->assertCount(1, $statements);

        $statement = $statements[0];
        $this->assertSame(Effect::ALLOW, $statement->effect());
        $this->assertCount(3, $statement->actions());
        $this->assertContains(Action::CREATE, $statement->actions());
        $this->assertContains(Action::EDIT, $statement->actions());
        $this->assertContains(Action::SUBMIT, $statement->actions());
        $this->assertSame(ResourceType::cases(), $statement->resourceTypes());
        $this->assertSame(ScopeCondition::NONE, $statement->scopeCondition());
    }

    /**
     * 正常系: AGENCY_MANAGEMENTはAPPROVE, REJECT, TRANSLATE, PUBLISHを全リソースに対してOWN_AGENCYスコープでALLOWを返すこと.
     *
     * @return void
     */
    public function testAgencyManagementStatements(): void
    {
        $statements = Policy::AGENCY_MANAGEMENT->statements();

        $this->assertCount(1, $statements);

        $statement = $statements[0];
        $this->assertSame(Effect::ALLOW, $statement->effect());
        $this->assertCount(4, $statement->actions());
        $this->assertContains(Action::APPROVE, $statement->actions());
        $this->assertContains(Action::REJECT, $statement->actions());
        $this->assertContains(Action::TRANSLATE, $statement->actions());
        $this->assertContains(Action::PUBLISH, $statement->actions());
        $this->assertSame(ResourceType::cases(), $statement->resourceTypes());
        $this->assertSame(ScopeCondition::OWN_AGENCY, $statement->scopeCondition());
    }

    /**
     * 正常系: GROUP_MANAGEMENTはAPPROVE, REJECT, TRANSLATE, PUBLISHをGROUP, TALENT, SONGに対してOWN_GROUPSスコープでALLOWを返すこと.
     *
     * @return void
     */
    public function testGroupManagementStatements(): void
    {
        $statements = Policy::GROUP_MANAGEMENT->statements();

        $this->assertCount(1, $statements);

        $statement = $statements[0];
        $this->assertSame(Effect::ALLOW, $statement->effect());
        $this->assertCount(4, $statement->actions());
        $this->assertContains(Action::APPROVE, $statement->actions());
        $this->assertContains(Action::REJECT, $statement->actions());
        $this->assertContains(Action::TRANSLATE, $statement->actions());
        $this->assertContains(Action::PUBLISH, $statement->actions());
        $this->assertCount(3, $statement->resourceTypes());
        $this->assertContains(ResourceType::GROUP, $statement->resourceTypes());
        $this->assertContains(ResourceType::TALENT, $statement->resourceTypes());
        $this->assertContains(ResourceType::SONG, $statement->resourceTypes());
        $this->assertSame(ScopeCondition::OWN_GROUPS, $statement->scopeCondition());
    }

    /**
     * 正常系: TALENT_MANAGEMENTは3つのStatementを返すこと.
     *
     * @return void
     */
    public function testTalentManagementStatements(): void
    {
        $statements = Policy::TALENT_MANAGEMENT->statements();

        $this->assertCount(3, $statements);

        // 共通のアクション
        $expectedActions = [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH];

        // GROUP に対する Statement
        $groupStatement = $statements[0];
        $this->assertSame(Effect::ALLOW, $groupStatement->effect());
        $this->assertCount(5, $groupStatement->actions());
        foreach ($expectedActions as $action) {
            $this->assertContains($action, $groupStatement->actions());
        }
        $this->assertSame([ResourceType::GROUP], $groupStatement->resourceTypes());
        $this->assertSame(ScopeCondition::OWN_GROUPS, $groupStatement->scopeCondition());

        // TALENT に対する Statement
        $talentStatement = $statements[1];
        $this->assertSame(Effect::ALLOW, $talentStatement->effect());
        $this->assertCount(5, $talentStatement->actions());
        foreach ($expectedActions as $action) {
            $this->assertContains($action, $talentStatement->actions());
        }
        $this->assertSame([ResourceType::TALENT], $talentStatement->resourceTypes());
        $this->assertSame(ScopeCondition::OWN_TALENTS, $talentStatement->scopeCondition());

        // SONG に対する Statement
        $songStatement = $statements[2];
        $this->assertSame(Effect::ALLOW, $songStatement->effect());
        $this->assertCount(5, $songStatement->actions());
        foreach ($expectedActions as $action) {
            $this->assertContains($action, $songStatement->actions());
        }
        $this->assertSame([ResourceType::SONG], $songStatement->resourceTypes());
        $this->assertSame(ScopeCondition::OWN_GROUPS_OR_TALENTS, $songStatement->scopeCondition());
    }

    /**
     * 正常系: DENY_AGENCY_APPROVALはAPPROVE, REJECT, TRANSLATE, PUBLISHをAGENCYに対してDENYを返すこと.
     *
     * @return void
     */
    public function testDenyAgencyApprovalStatements(): void
    {
        $statements = Policy::DENY_AGENCY_APPROVAL->statements();

        $this->assertCount(1, $statements);

        $statement = $statements[0];
        $this->assertSame(Effect::DENY, $statement->effect());
        $this->assertCount(4, $statement->actions());
        $this->assertContains(Action::APPROVE, $statement->actions());
        $this->assertContains(Action::REJECT, $statement->actions());
        $this->assertContains(Action::TRANSLATE, $statement->actions());
        $this->assertContains(Action::PUBLISH, $statement->actions());
        $this->assertSame([ResourceType::AGENCY], $statement->resourceTypes());
        $this->assertSame(ScopeCondition::NONE, $statement->scopeCondition());
    }

    /**
     * 正常系: DENY_ROLLBACKはROLLBACKを全リソースに対してDENYを返すこと.
     *
     * @return void
     */
    public function testDenyRollbackStatements(): void
    {
        $statements = Policy::DENY_ROLLBACK->statements();

        $this->assertCount(1, $statements);

        $statement = $statements[0];
        $this->assertSame(Effect::DENY, $statement->effect());
        $this->assertCount(1, $statement->actions());
        $this->assertContains(Action::ROLLBACK, $statement->actions());
        $this->assertSame(ResourceType::cases(), $statement->resourceTypes());
        $this->assertSame(ScopeCondition::NONE, $statement->scopeCondition());
    }
}
