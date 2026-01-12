<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use Source\Account\Affiliation\Domain\Event\AffiliationActivated;
use Source\Wiki\Principal\Domain\Factory\AffiliationGrantFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\AffiliationGrantRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;
use Source\Wiki\Principal\Domain\ValueObject\Condition;
use Source\Wiki\Principal\Domain\ValueObject\ConditionClause;
use Source\Wiki\Principal\Domain\ValueObject\ConditionKey;
use Source\Wiki\Principal\Domain\ValueObject\ConditionOperator;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;

readonly class AffiliationActivatedHandler
{
    public function __construct(
        private AffiliationGrantRepositoryInterface $affiliationGrantRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PolicyFactoryInterface $policyFactory,
        private PolicyRepositoryInterface $policyRepository,
        private RoleFactoryInterface $roleFactory,
        private RoleRepositoryInterface $roleRepository,
        private AffiliationGrantFactoryInterface $affiliationGrantFactory,
        private TalentRepositoryInterface $talentRepository,
    ) {
    }

    public function handle(AffiliationActivated $event): void
    {
        $this->createTalentSideGrant($event);
        $this->createAgencySideGrant($event);
    }

    private function createTalentSideGrant(AffiliationActivated $event): void
    {
        // 冪等性チェック
        $existing = $this->affiliationGrantRepository->findByAffiliationIdAndType(
            $event->affiliationIdentifier(),
            AffiliationGrantType::TALENT_SIDE
        );

        if ($existing !== null) {
            return;
        }

        // 専用 PrincipalGroup を新規作成
        $principalGroup = $this->principalGroupFactory->create(
            $event->talentAccountIdentifier(),
            "Affiliation - Agency {$event->agencyAccountIdentifier()}",
            false,
        );
        $this->principalGroupRepository->save($principalGroup);

        // Talent の全 Principal を新グループに追加
        $principals = $this->principalRepository->findByAccountId($event->talentAccountIdentifier());
        foreach ($principals as $principal) {
            $principalGroup->addMember($principal->principalIdentifier());
        }
        $this->principalGroupRepository->save($principalGroup);

        // Policy 作成（Agency の GROUP/SONG に対する権限）
        $agencyId = (string) $event->agencyAccountIdentifier();
        $policy = $this->policyFactory->create(
            "Affiliation Policy - Agency {$agencyId}",
            $this->createTalentSideStatements($agencyId),
            false,
        );
        $this->policyRepository->save($policy);

        // Role 作成
        $role = $this->roleFactory->create(
            "Affiliation Role - Agency {$event->agencyAccountIdentifier()}",
            [$policy->policyIdentifier()],
            false,
        );
        $this->roleRepository->save($role);

        // PrincipalGroup に Role をアタッチ
        $principalGroup->addRole($role->roleIdentifier());
        $this->principalGroupRepository->save($principalGroup);

        // AffiliationGrant 記録を保存
        $affiliationGrant = $this->affiliationGrantFactory->create(
            $event->affiliationIdentifier(),
            $policy->policyIdentifier(),
            $role->roleIdentifier(),
            $principalGroup->principalGroupIdentifier(),
            AffiliationGrantType::TALENT_SIDE,
        );
        $this->affiliationGrantRepository->save($affiliationGrant);
    }

    private function createAgencySideGrant(AffiliationActivated $event): void
    {
        // 冪等性チェック
        $existing = $this->affiliationGrantRepository->findByAffiliationIdAndType(
            $event->affiliationIdentifier(),
            AffiliationGrantType::AGENCY_SIDE
        );

        if ($existing !== null) {
            return;
        }

        // 専用 PrincipalGroup を新規作成（Principal は追加しない - UI 経由で後から追加）
        $principalGroup = $this->principalGroupFactory->create(
            $event->agencyAccountIdentifier(),
            "Affiliation - Talent {$event->talentAccountIdentifier()}",
            false,
        );
        $this->principalGroupRepository->save($principalGroup);

        // TalentAccountIdentifierからWiki側のTalentIdentifierを取得
        $talent = $this->talentRepository->findByOwnerAccountId($event->talentAccountIdentifier());
        $talentId = $talent !== null ? (string) $talent->talentIdentifier() : null;

        // Policy 作成（Talent に対する権限）
        $policy = $this->policyFactory->create(
            "Affiliation Policy - Talent {$event->talentAccountIdentifier()}",
            $this->createAgencySideStatements($talentId),
            false,
        );
        $this->policyRepository->save($policy);

        // Role 作成
        $role = $this->roleFactory->create(
            "Affiliation Role - Talent {$event->talentAccountIdentifier()}",
            [$policy->policyIdentifier()],
            false,
        );
        $this->roleRepository->save($role);

        // PrincipalGroup に Role をアタッチ
        $principalGroup->addRole($role->roleIdentifier());
        $this->principalGroupRepository->save($principalGroup);

        // AffiliationGrant 記録を保存
        $affiliationGrant = $this->affiliationGrantFactory->create(
            $event->affiliationIdentifier(),
            $policy->policyIdentifier(),
            $role->roleIdentifier(),
            $principalGroup->principalGroupIdentifier(),
            AffiliationGrantType::AGENCY_SIDE,
        );
        $this->affiliationGrantRepository->save($affiliationGrant);
    }

    /**
     * Talent側の Statement: Agency の GROUP/SONG に対する EDIT 権限.
     *
     * Group: AgencyがGroupに紐づいている AND 自身のTalentがそのGroupに紐づいている
     * Song: AgencyがSongに紐づいている AND (自身が所属するGroupがSongに紐づいている OR 自身のTalentがSongに紐づいている)
     *
     * @return Statement[]
     */
    private function createTalentSideStatements(string $agencyId): array
    {
        return [
            // Group: AgencyがGroupに紐づいている AND 自身のTalentがそのGroupに紐づいている
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT, Action::SUBMIT],
                [ResourceType::GROUP],
                new Condition([
                    new ConditionClause(ConditionKey::RESOURCE_AGENCY_ID, ConditionOperator::EQUALS, $agencyId),
                    new ConditionClause(ConditionKey::RESOURCE_TALENT_ID, ConditionOperator::IN, ConditionValue::PRINCIPAL_TALENT_IDS),
                ]),
            ),
            // Song (Group経由): AgencyがSongに紐づいている AND 自身が所属するGroupがSongに紐づいている
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT, Action::SUBMIT],
                [ResourceType::SONG],
                new Condition([
                    new ConditionClause(ConditionKey::RESOURCE_AGENCY_ID, ConditionOperator::EQUALS, $agencyId),
                    new ConditionClause(ConditionKey::RESOURCE_GROUP_ID, ConditionOperator::IN, ConditionValue::PRINCIPAL_WIKI_GROUP_IDS),
                ]),
            ),
            // Song (Talent経由): AgencyがSongに紐づいている AND 自身のTalentがSongに紐づいている
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT, Action::SUBMIT],
                [ResourceType::SONG],
                new Condition([
                    new ConditionClause(ConditionKey::RESOURCE_AGENCY_ID, ConditionOperator::EQUALS, $agencyId),
                    new ConditionClause(ConditionKey::RESOURCE_TALENT_ID, ConditionOperator::IN, ConditionValue::PRINCIPAL_TALENT_IDS),
                ]),
            ),
        ];
    }

    /**
     * Agency側の Statement: Talent に対する EDIT 権限.
     *
     * 指定されたTalentのみに対する権限を付与する.
     * TalentIdがnullの場合（公式Talentが存在しない場合）は権限なし.
     *
     * @return Statement[]
     */
    private function createAgencySideStatements(?string $talentId): array
    {
        if ($talentId === null) {
            return [];
        }

        return [
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT, Action::SUBMIT],
                [ResourceType::TALENT],
                new Condition([
                    new ConditionClause(ConditionKey::RESOURCE_TALENT_ID, ConditionOperator::IN, $talentId),
                ]),
            ),
        ];
    }
}
