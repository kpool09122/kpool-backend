<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use Source\Account\Affiliation\Domain\Event\AffiliationActivated;
use Source\Account\Shared\Domain\ValueObject\AccountType;
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
            "Affiliation - Agency {$event->agencyAccountName()}",
            false,
        );
        $this->principalGroupRepository->save($principalGroup);

        if ($event->talentAccountType() === AccountType::INDIVIDUAL) {
            // Talent の全 Principal を新グループに追加
            $principals = $this->principalRepository->findByAccountId($event->talentAccountIdentifier());
            foreach ($principals as $principal) {
                $principalGroup->addMember($principal->principalIdentifier());
            }
            $this->principalGroupRepository->save($principalGroup);
        }

        // Policy 作成（Agency の GROUP/SONG に対する権限）
        $agencyId = (string) $event->agencyAccountIdentifier();
        $policy = $this->policyFactory->create(
            "Affiliation Policy - Agency {$event->agencyAccountName()}",
            $this->createTalentSideStatements($agencyId),
            false,
        );
        $this->policyRepository->save($policy);

        // Role 作成
        $role = $this->roleFactory->create(
            "Affiliation Role - Agency {$event->agencyAccountName()}",
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
            "Affiliation - Talent {$event->talentAccountName()}",
            false,
        );
        $this->principalGroupRepository->save($principalGroup);

        // Policy 作成（Talent に対する権限）
        // Talent Wiki は評価時に動的解決するため、Affiliation 成立時点で未作成でも空 Policy にしない。
        $policy = $this->policyFactory->create(
            "Affiliation Policy - Talent {$event->talentAccountName()}",
            $this->createAgencySideStatements(),
            false,
        );
        $this->policyRepository->save($policy);

        // Role 作成
        $role = $this->roleFactory->create(
            "Affiliation Role - Talent {$event->talentAccountName()}",
            [$policy->policyIdentifier()],
            false,
        );
        $this->roleRepository->save($role);

        // PrincipalGroup に Role をアタッチ
        $principalGroup->addRole($role->roleIdentifier());
        if ($event->agencyAccountType() === AccountType::INDIVIDUAL) {
            $principals = $this->principalRepository->findByAccountId($event->agencyAccountIdentifier());
            foreach ($principals as $principal) {
                $principalGroup->addMember($principal->principalIdentifier());
            }
        }
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
                [Action::READ, Action::CREATE, Action::EDIT, Action::SUBMIT],
                [ResourceType::GROUP],
                new Condition([
                    new ConditionClause(ConditionKey::RESOURCE_AGENCY_ID, ConditionOperator::EQUALS, $agencyId),
                    new ConditionClause(ConditionKey::RESOURCE_TALENT_ID, ConditionOperator::IN, ConditionValue::PRINCIPAL_TALENT_WIKI_IDENTIFIERS),
                ]),
            ),
            // Song (Group経由): AgencyがSongに紐づいている AND 自身が所属するGroupがSongに紐づいている
            new Statement(
                Effect::ALLOW,
                [Action::READ, Action::CREATE, Action::EDIT, Action::SUBMIT],
                [ResourceType::SONG],
                new Condition([
                    new ConditionClause(ConditionKey::RESOURCE_AGENCY_ID, ConditionOperator::EQUALS, $agencyId),
                    new ConditionClause(ConditionKey::RESOURCE_GROUP_ID, ConditionOperator::IN, ConditionValue::PRINCIPAL_TALENT_GROUP_WIKI_IDENTIFIERS),
                ]),
            ),
            // Song (Talent経由): AgencyがSongに紐づいている AND 自身のTalentがSongに紐づいている
            new Statement(
                Effect::ALLOW,
                [Action::READ, Action::CREATE, Action::EDIT, Action::SUBMIT],
                [ResourceType::SONG],
                new Condition([
                    new ConditionClause(ConditionKey::RESOURCE_AGENCY_ID, ConditionOperator::EQUALS, $agencyId),
                    new ConditionClause(ConditionKey::RESOURCE_TALENT_ID, ConditionOperator::IN, ConditionValue::PRINCIPAL_TALENT_WIKI_IDENTIFIERS),
                ]),
            ),
        ];
    }

    /**
     * Agency側の Statement: Talent に対する EDIT 権限.
     *
     * active Affiliation と評価時点の Talent Wiki 所有情報から動的に対象 Talent を解決する.
     * Talent Wiki が存在しない場合は resolver が空配列を返し、暗黙的に権限なしになる.
     *
     * @return Statement[]
     */
    private function createAgencySideStatements(): array
    {
        return [
            new Statement(
                Effect::ALLOW,
                [Action::READ, Action::CREATE, Action::EDIT, Action::SUBMIT],
                [ResourceType::TALENT],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_TALENT_ID,
                        ConditionOperator::IN,
                        ConditionValue::PRINCIPAL_AFFILIATED_TALENT_WIKI_IDENTIFIERS,
                    ),
                ]),
            ),
        ];
    }
}
