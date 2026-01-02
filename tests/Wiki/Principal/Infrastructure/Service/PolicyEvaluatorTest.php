<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PolicyEvaluatorTest extends TestCase
{
    /**
     * 正常系: Administratorの場合は、いつでもtrueが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testAdministratorAlwaysTrue(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);
        $resource = new ResourceIdentifier(ResourceType::AGENCY);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);
        foreach (Action::cases() as $action) {
            $this->assertTrue($policyEvaluator->evaluate($principal, $action, $resource));
        }
    }

    /**
     * 正常系: SeniorCollaboratorの場合は、いつでもtrueが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSeniorCollaboratorAlwaysTrue(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::SENIOR_COLLABORATOR, null, [], []);
        $resource = new ResourceIdentifier(ResourceType::AGENCY);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);
        foreach (Action::cases() as $action) {
            $this->assertTrue($policyEvaluator->evaluate($principal, $action, $resource));
        }
    }

    /**
     * 正常系: ロールが存在しない場合は、いつでもfalseが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testNoneAlwaysFalse(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);
        $resource = new ResourceIdentifier(ResourceType::AGENCY);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);
        foreach (Action::cases() as $action) {
            $this->assertFalse($policyEvaluator->evaluate($principal, $action, $resource));
        }
    }

    /**
     * 正常系: Agency Actorの場合に、正しい権限判定が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testAgencyActorScopedToOwnAgency(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $agencyId, [], []);
        $notOwningAgencyActor = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, null, [], []);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);

        // Agency 自身
        $agencyOwned = new ResourceIdentifier(ResourceType::AGENCY, $agencyId);
        $noAgency = new ResourceIdentifier(ResourceType::AGENCY);
        $agencyOther = new ResourceIdentifier(ResourceType::AGENCY, StrTestHelper::generateUuid());
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $agencyOwned));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $agencyOwned));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $noAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $noAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $agencyOther));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $agencyOther));
        $this->assertFalse($policyEvaluator->evaluate($notOwningAgencyActor, Action::APPROVE, $agencyOwned));
        $this->assertFalse($policyEvaluator->evaluate($notOwningAgencyActor, Action::TRANSLATE, $agencyOwned));

        // Group（agencyId が一致している必要がある）
        $groupInAgency = new ResourceIdentifier(ResourceType::GROUP, $agencyId);
        $groupOtherAgency = new ResourceIdentifier(ResourceType::GROUP, StrTestHelper::generateUuid());
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $groupInAgency));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $groupInAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $groupOtherAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $groupOtherAgency));

        // Talent（agencyId で判定）
        $g1 = StrTestHelper::generateUuid();
        $g2 = StrTestHelper::generateUuid();
        $talentInAgency = new ResourceIdentifier(ResourceType::TALENT, $agencyId, [$g1, $g2]);
        $talentNoAgency = new ResourceIdentifier(ResourceType::TALENT, null, [$g1]);
        $talentOtherAgency = new ResourceIdentifier(ResourceType::TALENT, StrTestHelper::generateUuid(), [$g2]);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $talentInAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $talentNoAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $talentOtherAgency));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $talentInAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $talentNoAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $talentOtherAgency));

        // Song（agencyId で判定）
        $songInAgency = new ResourceIdentifier(ResourceType::SONG, $agencyId, [$g1]);
        $songOtherAgency = new ResourceIdentifier(ResourceType::SONG, StrTestHelper::generateUuid(), [$g1]);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $songInAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $songOtherAgency));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $songInAgency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $songOtherAgency));
    }

    /**
     * 正常系: Group Actorの場合に、正しい権限判定が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGroupActorApproveGroupOnlyInOwnGroups(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupId1 = StrTestHelper::generateUuid();
        $groupId2 = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, null, [$groupId1], []);

        $groupOwned = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId1]);
        $groupNotOwned = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId2]);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);

        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $groupOwned));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $groupNotOwned));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $groupOwned));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $groupNotOwned));
    }

    /**
     * 正常系: Group Actorの場合に、正しい権限判定が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGroupActorApproveTalentOrSongRequiresMatchingGroupIds(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupId = StrTestHelper::generateUuid();
        $anotherGroupId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, null, [$groupId], []);

        $talentInGroup = new ResourceIdentifier(ResourceType::TALENT, null, [$groupId]);
        $talentNoGroup = new ResourceIdentifier(ResourceType::TALENT);
        $talentOtherGroup = new ResourceIdentifier(ResourceType::TALENT, null, [$anotherGroupId]);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);

        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $talentInGroup));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $talentNoGroup));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $talentOtherGroup));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $talentInGroup));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $talentNoGroup));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $talentOtherGroup));

        $songInGroup = new ResourceIdentifier(ResourceType::SONG, null, [$groupId]);
        $songOtherGroup = new ResourceIdentifier(ResourceType::SONG, null, [$anotherGroupId]);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $songInGroup));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $songInGroup));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $songOtherGroup));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $songOtherGroup));
    }

    /**
     * 正常系: Group Actorの場合に、Agencyの承認をできないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGroupActorCannotApproveAgency(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, null, [StrTestHelper::generateUuid()], []);
        $agency = new ResourceIdentifier(ResourceType::AGENCY);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);

        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $agency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $agency));
    }

    /**
     * 正常系: Group Actorの場合に、Agencyの編集はできること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGroupActorCanEditAgency(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, null, [], []);
        $agency = new ResourceIdentifier(ResourceType::AGENCY);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);

        $this->assertTrue($policyEvaluator->evaluate($principal, Action::EDIT, $agency));
    }

    /**
     * 正常系: Talent Actorの場合に、正しい権限判定が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testTalentActorScopeChecks(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, null, [$groupId], [$talentId]);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);

        // TALENT リソース（自分自身のTalentのみ承認可能）
        $ownTalent = new ResourceIdentifier(ResourceType::TALENT, null, [$groupId], [$talentId]);
        $otherTalent = new ResourceIdentifier(ResourceType::TALENT, null, [$groupId], [StrTestHelper::generateUuid()]);

        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $ownTalent));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $otherTalent));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $ownTalent));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $otherTalent));

        // GROUP リソース（groupIds の交差でチェック）
        $groupOwned = new ResourceIdentifier(ResourceType::GROUP, null, [$groupId]);
        $groupNotOwned = new ResourceIdentifier(ResourceType::GROUP, null, [StrTestHelper::generateUuid()]);
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $groupOwned));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $groupNotOwned));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::TRANSLATE, $groupOwned));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $groupNotOwned));
    }

    /**
     * 正常系: Talent Actorの場合に、Agencyの承認ができないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testTalentActorCannotApproveAgency(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, null, [StrTestHelper::generateUuid()], [StrTestHelper::generateUuid()]);
        $agency = new ResourceIdentifier(ResourceType::AGENCY);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);

        // TALENT_ACTOR は Agency に対して APPROVE, REJECT, TRANSLATE, PUBLISH ができない
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $agency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::REJECT, $agency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $agency));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::PUBLISH, $agency));
    }

    /**
     * 正常系: Talent Actorの場合に、Agencyの作成、編集、申請はできること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testTalentActorCanCreateEditAndSubmitAgency(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, null, [], []);
        $agency = new ResourceIdentifier(ResourceType::AGENCY);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);

        // TALENT_ACTOR は Agency に対して CREATE, EDIT, SUBMIT ができる
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::CREATE, $agency));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::EDIT, $agency));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::SUBMIT, $agency));
    }

    /**
     * 正常系: Collaboratorの場合に、作成、編集、申請の権限のみ持っていること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCollaboratorBasicOnly(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::COLLABORATOR, null, [], []);
        $group = new ResourceIdentifier(ResourceType::GROUP);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);

        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $group));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::TRANSLATE, $group));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::EDIT, $group));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::CREATE, $group));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::SUBMIT, $group));
    }

    /**
     * 正常系: Talent Actorの場合に、承認したいSongに紐づくGroupかTalentのIDに紐づけられていれば、承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testTalentActorSongWithGroupsOrTalents(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, null, [$groupId], [$talentId]);

        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);

        // SONG リソース - EDIT は BASIC_EDITING で全リソースに許可
        $songByGroup = new ResourceIdentifier(ResourceType::SONG, null, [$groupId], []);
        $songByTalent = new ResourceIdentifier(ResourceType::SONG, null, [], [$talentId]);
        $songByBoth = new ResourceIdentifier(ResourceType::SONG, null, [$groupId], [$talentId]);
        $songByNeither = new ResourceIdentifier(ResourceType::SONG, null, [StrTestHelper::generateUuid()], [StrTestHelper::generateUuid()]);

        // EDIT は BASIC_EDITING で全リソースに許可されている
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::EDIT, $songByGroup));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::EDIT, $songByTalent));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::EDIT, $songByBoth));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::EDIT, $songByNeither));

        // APPROVE は groupIds OR talentIds のどちらかが一致する必要がある
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $songByGroup));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $songByTalent));
        $this->assertTrue($policyEvaluator->evaluate($principal, Action::APPROVE, $songByBoth));
        $this->assertFalse($policyEvaluator->evaluate($principal, Action::APPROVE, $songByNeither));
    }
}
