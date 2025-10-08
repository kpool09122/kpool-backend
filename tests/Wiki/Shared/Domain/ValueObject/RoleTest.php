<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RoleTest extends TestCase
{
    /**
     * 正常系：allowedActionsFor が各ロール/リソースタイプに応じた配列を返すこと.
     */
    public function testAllowedActionsFor(): void
    {
        // Administrator / Agency actor は全アクション許可
        $allActions = [Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH];
        $this->assertSame($allActions, Role::ADMINISTRATOR->allowedActionsFor(ResourceType::AGENCY));
        $this->assertSame($allActions, Role::AGENCY_ACTOR->allowedActionsFor(ResourceType::GROUP));

        // Group / Talent actor は Agency のみ承認・却下・翻訳・公開不可、その他は許可
        $basicActions = [Action::CREATE, Action::EDIT, Action::SUBMIT];
        $this->assertSame($basicActions, Role::GROUP_ACTOR->allowedActionsFor(ResourceType::AGENCY));
        $this->assertSame($allActions, Role::GROUP_ACTOR->allowedActionsFor(ResourceType::GROUP));
        $this->assertSame($basicActions, Role::TALENT_ACTOR->allowedActionsFor(ResourceType::AGENCY));
        $this->assertSame($allActions, Role::TALENT_ACTOR->allowedActionsFor(ResourceType::SONG));

        // Collaborator は基本アクションのみ
        $this->assertSame($basicActions, Role::COLLABORATOR->allowedActionsFor(ResourceType::AGENCY));
        $this->assertSame($basicActions, Role::COLLABORATOR->allowedActionsFor(ResourceType::GROUP));
    }

    /**
     * 正常系：Administrator は常に true を返すこと.
     */
    public function testCanAdministratorAlwaysTrue(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);
        $resource = new ResourceIdentifier(ResourceType::AGENCY);

        foreach ([Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH] as $action) {
            $this->assertTrue(Role::ADMINISTRATOR->can($action, $resource, $principal));
        }
    }

    /**
     * 正常系/異常系：Agency actor は自分の agency に紐づくもののみ編集/承認/翻訳できる.
     */
    public function testCanAgencyActorScopedToOwnAgency(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $agencyId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], null);
        $notOwningAgencyActor = new Principal($principalIdentifier, Role::AGENCY_ACTOR, null, [], null);

        // Agency 自身
        $agencyOwned = new ResourceIdentifier(ResourceType::AGENCY, $agencyId);
        $noAgency = new ResourceIdentifier(ResourceType::AGENCY);
        $agencyOther = new ResourceIdentifier(ResourceType::AGENCY, StrTestHelper::generateUlid());
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::APPROVE, $agencyOwned, $principal));
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $agencyOwned, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $noAgency, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $noAgency, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $agencyOther, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $agencyOther, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $agencyOwned, $notOwningAgencyActor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $agencyOwned, $notOwningAgencyActor));

        // Group（agencyId が一致している必要がある）
        $groupInAgency = new ResourceIdentifier(ResourceType::GROUP, $agencyId);
        $groupOtherAgency = new ResourceIdentifier(ResourceType::GROUP, StrTestHelper::generateUlid());
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::APPROVE, $groupInAgency, $principal));
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $groupInAgency, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $groupOtherAgency, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $groupOtherAgency, $principal));

        // Talent（agencyId で判定）
        $g1 = StrTestHelper::generateUlid();
        $g2 = StrTestHelper::generateUlid();
        $talentInAgency = new ResourceIdentifier(ResourceType::TALENT, $agencyId, [$g1, $g2]);
        $talentNoAgency = new ResourceIdentifier(ResourceType::TALENT, null, [$g1]);
        $talentOtherAgency = new ResourceIdentifier(ResourceType::TALENT, StrTestHelper::generateUlid(), [$g2]);
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::APPROVE, $talentInAgency, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $talentNoAgency, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $talentOtherAgency, $principal));
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $talentInAgency, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $talentNoAgency, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $talentOtherAgency, $principal));

        // Song（agencyId で判定）
        $songInAgency = new ResourceIdentifier(ResourceType::SONG, $agencyId, [$g1]);
        $songOtherAgency = new ResourceIdentifier(ResourceType::SONG, StrTestHelper::generateUlid(), [$g1]);
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::APPROVE, $songInAgency, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $songOtherAgency, $principal));
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $songInAgency, $principal));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $songOtherAgency, $principal));
    }

    /**
     * 正常系：Group actor は自グループに紐づくリソースのみ承認/翻訳できる（Group リソース）.
     */
    public function testCanGroupActorApproveGroupOnlyInOwnGroups(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId1 = StrTestHelper::generateUlid();
        $groupId2 = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$groupId1], null);

        $groupOwned = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId1]);
        $groupNotOwned = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId2]);

        $this->assertTrue(Role::GROUP_ACTOR->can(Action::APPROVE, $groupOwned, $principal));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::APPROVE, $groupNotOwned, $principal));
        $this->assertTrue(Role::GROUP_ACTOR->can(Action::TRANSLATE, $groupOwned, $principal));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::TRANSLATE, $groupNotOwned, $principal));
    }

    /**
     * 正常系：Group actor は Talent/Song の承認/翻訳で groupIds の交差をチェックする.
     */
    public function testCanGroupActorApproveTalentOrSongRequiresMatchingGroupIds(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$groupId], null);

        $talentInGroup = new ResourceIdentifier(ResourceType::TALENT, null, [$groupId]);
        $talentNoGroup = new ResourceIdentifier(ResourceType::TALENT);
        $talentOtherGroup = new ResourceIdentifier(ResourceType::TALENT, null, [$anotherGroupId]);

        $this->assertTrue(Role::GROUP_ACTOR->can(Action::APPROVE, $talentInGroup, $principal));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::APPROVE, $talentNoGroup, $principal));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::APPROVE, $talentOtherGroup, $principal));
        $this->assertTrue(Role::GROUP_ACTOR->can(Action::TRANSLATE, $talentInGroup, $principal));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::TRANSLATE, $talentNoGroup, $principal));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::TRANSLATE, $talentOtherGroup, $principal));

        $songInGroup = new ResourceIdentifier(ResourceType::SONG, null, [$groupId]);
        $songOtherGroup = new ResourceIdentifier(ResourceType::SONG, null, [$anotherGroupId]);
        $this->assertTrue(Role::GROUP_ACTOR->can(Action::APPROVE, $songInGroup, $principal));
        $this->assertTrue(Role::GROUP_ACTOR->can(Action::TRANSLATE, $songInGroup, $principal));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::APPROVE, $songOtherGroup, $principal));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::TRANSLATE, $songOtherGroup, $principal));
    }

    /**
     * 正常系：Group actor は Agency の承認/翻訳はできない.
     */
    public function testCanGroupActorCannotApproveAgency(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [StrTestHelper::generateUlid()], null);
        $agency = new ResourceIdentifier(ResourceType::AGENCY);

        $this->assertFalse(Role::GROUP_ACTOR->can(Action::APPROVE, $agency, $principal));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::TRANSLATE, $agency, $principal));
    }

    /**
     * 正常系：Group actor は Agency に対する編集等の基本アクションは可能.
     */
    public function testCanGroupActorCanEditAgency(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [], null);
        $agency = new ResourceIdentifier(ResourceType::AGENCY);

        $this->assertTrue(Role::GROUP_ACTOR->can(Action::EDIT, $agency, $principal));
    }

    /**
     * 正常系：Talent actor は自分の所属グループ内のリソースのみ承認/翻訳可能.
     */
    public function testCanTalentActorScopeChecksGroupId(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$groupId], StrTestHelper::generateUlid());

        // MEMBER リソース
        $talentInGroup = new ResourceIdentifier(ResourceType::TALENT, null, [$groupId]);
        $talentNoGroup = new ResourceIdentifier(ResourceType::TALENT, null, []);
        $talentOtherGroup = new ResourceIdentifier(ResourceType::TALENT, null, [StrTestHelper::generateUlid()]);
        $this->assertTrue(Role::TALENT_ACTOR->can(Action::APPROVE, $talentInGroup, $principal));
        $this->assertFalse(Role::TALENT_ACTOR->can(Action::APPROVE, $talentNoGroup, $principal));
        $this->assertFalse(Role::TALENT_ACTOR->can(Action::APPROVE, $talentOtherGroup, $principal));
        $this->assertTrue(Role::TALENT_ACTOR->can(Action::TRANSLATE, $talentInGroup, $principal));
        $this->assertFalse(Role::TALENT_ACTOR->can(Action::APPROVE, $talentNoGroup, $principal));
        $this->assertFalse(Role::TALENT_ACTOR->can(Action::TRANSLATE, $talentOtherGroup, $principal));

        // GROUP リソース（groupIds の交差でチェック）
        $groupOwned = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId]);
        $groupNotOwned = new ResourceIdentifier(ResourceType::GROUP, null, [StrTestHelper::generateUlid()]);
        $this->assertTrue(Role::TALENT_ACTOR->can(Action::APPROVE, $groupOwned, $principal));
        $this->assertFalse(Role::TALENT_ACTOR->can(Action::APPROVE, $groupNotOwned, $principal));
        $this->assertTrue(Role::TALENT_ACTOR->can(Action::TRANSLATE, $groupOwned, $principal));
        $this->assertFalse(Role::TALENT_ACTOR->can(Action::TRANSLATE, $groupNotOwned, $principal));
    }

    /**
     * 正常系：Collaborator は承認不可だが基本アクションは可能.
     */
    public function testCanCollaboratorBasicOnly(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], null);
        $group = new ResourceIdentifier(ResourceType::GROUP);

        $this->assertFalse(Role::COLLABORATOR->can(Action::APPROVE, $group, $principal));
        $this->assertFalse(Role::COLLABORATOR->can(Action::TRANSLATE, $group, $principal));
        $this->assertTrue(Role::COLLABORATOR->can(Action::EDIT, $group, $principal));
    }

    /**
     * 正常系：SENIOR_COLLABORATORは常にtrueを返すこと.
     */
    public function testCanSeniorCollaboratorAlwaysTrue(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::SENIOR_COLLABORATOR, null, [], null);
        $resource = new ResourceIdentifier(ResourceType::AGENCY);

        foreach ([Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH] as $action) {
            $this->assertTrue(Role::SENIOR_COLLABORATOR->can($action, $resource, $principal));
        }
    }

    /**
     * 異常系：NONEは常にfalseを返すこと.
     */
    public function testCanNoneAlwaysFalse(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::NONE, null, [], null);
        $resource = new ResourceIdentifier(ResourceType::AGENCY);

        foreach ([Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH] as $action) {
            $this->assertFalse(Role::NONE->can($action, $resource, $principal));
        }
    }

    /**
     * 正常系：SENIOR_COLLABORATORがallowedActionsForで全アクション許可を返すこと.
     */
    public function testAllowedActionsForSeniorCollaborator(): void
    {
        $allActions = [Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH];
        $this->assertSame($allActions, Role::SENIOR_COLLABORATOR->allowedActionsFor(ResourceType::AGENCY));
        $this->assertSame($allActions, Role::SENIOR_COLLABORATOR->allowedActionsFor(ResourceType::GROUP));
        $this->assertSame($allActions, Role::SENIOR_COLLABORATOR->allowedActionsFor(ResourceType::TALENT));
        $this->assertSame($allActions, Role::SENIOR_COLLABORATOR->allowedActionsFor(ResourceType::SONG));
    }

    /**
     * 正常系：NONEがallowedActionsForで空配列を返すこと.
     */
    public function testAllowedActionsForNone(): void
    {
        $this->assertSame([], Role::NONE->allowedActionsFor(ResourceType::AGENCY));
        $this->assertSame([], Role::NONE->allowedActionsFor(ResourceType::GROUP));
        $this->assertSame([], Role::NONE->allowedActionsFor(ResourceType::TALENT));
        $this->assertSame([], Role::NONE->allowedActionsFor(ResourceType::SONG));
    }
}
