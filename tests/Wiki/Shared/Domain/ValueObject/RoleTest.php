<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Actor;
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
        $allActions = [Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::TRANSLATE];
        $this->assertSame($allActions, Role::ADMINISTRATOR->allowedActionsFor(ResourceType::AGENCY));
        $this->assertSame($allActions, Role::AGENCY_ACTOR->allowedActionsFor(ResourceType::GROUP));

        // Group / Member actor は Agency のみ承認・翻訳不可、その他は許可
        $basicActions = [Action::CREATE, Action::EDIT, Action::SUBMIT];
        $this->assertSame($basicActions, Role::GROUP_ACTOR->allowedActionsFor(ResourceType::AGENCY));
        $this->assertSame($allActions, Role::GROUP_ACTOR->allowedActionsFor(ResourceType::GROUP));
        $this->assertSame($basicActions, Role::MEMBER_ACTOR->allowedActionsFor(ResourceType::AGENCY));
        $this->assertSame($allActions, Role::MEMBER_ACTOR->allowedActionsFor(ResourceType::SONG));

        // Collaborator は基本アクションのみ
        $this->assertSame($basicActions, Role::COLLABORATOR->allowedActionsFor(ResourceType::AGENCY));
        $this->assertSame($basicActions, Role::COLLABORATOR->allowedActionsFor(ResourceType::GROUP));
    }

    /**
     * 正常系：Administrator は常に true を返すこと.
     */
    public function testCanAdministratorAlwaysTrue(): void
    {
        $actor = new Actor(Role::ADMINISTRATOR, null, [], null);
        $resource = new ResourceIdentifier(ResourceType::AGENCY, StrTestHelper::generateUlid());

        foreach ([Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::TRANSLATE] as $action) {
            $this->assertTrue(Role::ADMINISTRATOR->can($action, $resource, $actor));
        }
    }

    /**
     * 正常系/異常系：Agency actor は自分の agency に紐づくもののみ編集/承認/翻訳できる.
     */
    public function testCanAgencyActorScopedToOwnAgency(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $actor = new Actor(Role::AGENCY_ACTOR, $agencyId, [], null);

        // Agency 自身
        $agencyOwned = new ResourceIdentifier(ResourceType::AGENCY, $agencyId);
        $agencyOther = new ResourceIdentifier(ResourceType::AGENCY, StrTestHelper::generateUlid());
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::APPROVE, $agencyOwned, $actor));
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $agencyOwned, $actor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $agencyOther, $actor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $agencyOther, $actor));

        // Group（agencyId が一致している必要がある）
        $groupInAgency = new ResourceIdentifier(ResourceType::GROUP, StrTestHelper::generateUlid(), $agencyId);
        $groupOtherAgency = new ResourceIdentifier(ResourceType::GROUP, StrTestHelper::generateUlid(), StrTestHelper::generateUlid());
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::APPROVE, $groupInAgency, $actor));
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $groupInAgency, $actor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $groupOtherAgency, $actor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $groupOtherAgency, $actor));

        // Member（agencyId で判定）
        $g1 = StrTestHelper::generateUlid();
        $g2 = StrTestHelper::generateUlid();
        $memberInAgency = new ResourceIdentifier(ResourceType::MEMBER, StrTestHelper::generateUlid(), $agencyId, [$g1, $g2]);
        $memberNoAgency = new ResourceIdentifier(ResourceType::MEMBER, StrTestHelper::generateUlid(), null, [$g1]);
        $memberOtherAgency = new ResourceIdentifier(ResourceType::MEMBER, StrTestHelper::generateUlid(), StrTestHelper::generateUlid(), [$g2]);
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::APPROVE, $memberInAgency, $actor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $memberNoAgency, $actor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $memberOtherAgency, $actor));
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $memberInAgency, $actor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $memberNoAgency, $actor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $memberOtherAgency, $actor));

        // Song（agencyId で判定）
        $songInAgency = new ResourceIdentifier(ResourceType::SONG, StrTestHelper::generateUlid(), $agencyId, [$g1]);
        $songOtherAgency = new ResourceIdentifier(ResourceType::SONG, StrTestHelper::generateUlid(), StrTestHelper::generateUlid(), [$g1]);
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::APPROVE, $songInAgency, $actor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::APPROVE, $songOtherAgency, $actor));
        $this->assertTrue(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $songInAgency, $actor));
        $this->assertFalse(Role::AGENCY_ACTOR->can(Action::TRANSLATE, $songOtherAgency, $actor));
    }

    /**
     * 正常系：Group actor は自グループに紐づくリソースのみ承認/翻訳できる（Group リソース）.
     */
    public function testCanGroupActorApproveGroupOnlyInOwnGroups(): void
    {
        $groupId1 = StrTestHelper::generateUlid();
        $groupId2 = StrTestHelper::generateUlid();
        $actor = new Actor(Role::GROUP_ACTOR, null, [$groupId1], null);

        $groupOwned = new ResourceIdentifier(ResourceType::GROUP, $groupId1);
        $groupNotOwned = new ResourceIdentifier(ResourceType::GROUP, $groupId2);

        $this->assertTrue(Role::GROUP_ACTOR->can(Action::APPROVE, $groupOwned, $actor));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::APPROVE, $groupNotOwned, $actor));
        $this->assertTrue(Role::GROUP_ACTOR->can(Action::TRANSLATE, $groupOwned, $actor));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::TRANSLATE, $groupNotOwned, $actor));
    }

    /**
     * 正常系：Group actor は Member/Song の承認/翻訳で groupIds の交差をチェックする.
     */
    public function testCanGroupActorApproveMemberOrSongRequiresMatchingGroupIds(): void
    {
        $groupId = StrTestHelper::generateUlid();
        $anotherGroupId = StrTestHelper::generateUlid();
        $actor = new Actor(Role::GROUP_ACTOR, null, [$groupId], null);

        $memberInGroup = new ResourceIdentifier(ResourceType::MEMBER, StrTestHelper::generateUlid(), null, [$groupId]);
        $memberNoGroup = new ResourceIdentifier(ResourceType::MEMBER, StrTestHelper::generateUlid());
        $memberOtherGroup = new ResourceIdentifier(ResourceType::MEMBER, StrTestHelper::generateUlid(), null, [$anotherGroupId]);

        $this->assertTrue(Role::GROUP_ACTOR->can(Action::APPROVE, $memberInGroup, $actor));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::APPROVE, $memberNoGroup, $actor));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::APPROVE, $memberOtherGroup, $actor));
        $this->assertTrue(Role::GROUP_ACTOR->can(Action::TRANSLATE, $memberInGroup, $actor));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::TRANSLATE, $memberNoGroup, $actor));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::TRANSLATE, $memberOtherGroup, $actor));

        $songInGroup = new ResourceIdentifier(ResourceType::SONG, StrTestHelper::generateUlid(), null, [$groupId]);
        $songOtherGroup = new ResourceIdentifier(ResourceType::SONG, StrTestHelper::generateUlid(), null, [$anotherGroupId]);
        $this->assertTrue(Role::GROUP_ACTOR->can(Action::APPROVE, $songInGroup, $actor));
        $this->assertTrue(Role::GROUP_ACTOR->can(Action::TRANSLATE, $songInGroup, $actor));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::APPROVE, $songOtherGroup, $actor));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::TRANSLATE, $songOtherGroup, $actor));
    }

    /**
     * 正常系：Group actor は Agency の承認/翻訳はできない.
     */
    public function testCanGroupActorCannotApproveAgency(): void
    {
        $actor = new Actor(Role::GROUP_ACTOR, null, [StrTestHelper::generateUlid()], null);
        $agency = new ResourceIdentifier(ResourceType::AGENCY, StrTestHelper::generateUlid());

        $this->assertFalse(Role::GROUP_ACTOR->can(Action::APPROVE, $agency, $actor));
        $this->assertFalse(Role::GROUP_ACTOR->can(Action::TRANSLATE, $agency, $actor));
    }

    /**
     * 正常系：Group actor は Agency に対する編集等の基本アクションは可能.
     */
    public function testCanGroupActorCanEditAgency(): void
    {
        $actor = new Actor(Role::GROUP_ACTOR, null, [], null);
        $agency = new ResourceIdentifier(ResourceType::AGENCY, StrTestHelper::generateUlid());

        $this->assertTrue(Role::GROUP_ACTOR->can(Action::EDIT, $agency, $actor));
    }

    /**
     * 正常系：Member actor は自分の所属グループ内のリソースのみ承認/翻訳可能.
     */
    public function testCanMemberActorScopeChecksGroupId(): void
    {
        $groupId = StrTestHelper::generateUlid();
        $actor = new Actor(Role::MEMBER_ACTOR, null, [$groupId], StrTestHelper::generateUlid());

        // MEMBER リソース
        $memberInGroup = new ResourceIdentifier(ResourceType::MEMBER, StrTestHelper::generateUlid(), null, [$groupId]);
        $memberOtherGroup = new ResourceIdentifier(ResourceType::MEMBER, StrTestHelper::generateUlid(), null, [StrTestHelper::generateUlid()]);
        $this->assertTrue(Role::MEMBER_ACTOR->can(Action::APPROVE, $memberInGroup, $actor));
        $this->assertFalse(Role::MEMBER_ACTOR->can(Action::APPROVE, $memberOtherGroup, $actor));
        $this->assertTrue(Role::MEMBER_ACTOR->can(Action::TRANSLATE, $memberInGroup, $actor));
        $this->assertFalse(Role::MEMBER_ACTOR->can(Action::TRANSLATE, $memberOtherGroup, $actor));

        // GROUP リソース（groupIds の交差でチェック）
        $groupOwned = new ResourceIdentifier(ResourceType::GROUP, StrTestHelper::generateUlid(), null, [$groupId]);
        $groupNotOwned = new ResourceIdentifier(ResourceType::GROUP, StrTestHelper::generateUlid(), null, [StrTestHelper::generateUlid()]);
        $this->assertTrue(Role::MEMBER_ACTOR->can(Action::APPROVE, $groupOwned, $actor));
        $this->assertFalse(Role::MEMBER_ACTOR->can(Action::APPROVE, $groupNotOwned, $actor));
        $this->assertTrue(Role::MEMBER_ACTOR->can(Action::TRANSLATE, $groupOwned, $actor));
        $this->assertFalse(Role::MEMBER_ACTOR->can(Action::TRANSLATE, $groupNotOwned, $actor));
    }

    /**
     * 正常系：Collaborator は承認不可だが基本アクションは可能.
     */
    public function testCanCollaboratorBasicOnly(): void
    {
        $actor = new Actor(Role::COLLABORATOR, null, [], null);
        $group = new ResourceIdentifier(ResourceType::GROUP, StrTestHelper::generateUlid());

        $this->assertFalse(Role::COLLABORATOR->can(Action::APPROVE, $group, $actor));
        $this->assertFalse(Role::COLLABORATOR->can(Action::TRANSLATE, $group, $actor));
        $this->assertTrue(Role::COLLABORATOR->can(Action::EDIT, $group, $actor));
    }
}
