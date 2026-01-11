<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Source\Wiki\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Condition;
use Source\Wiki\Principal\Domain\ValueObject\ConditionClause;
use Source\Wiki\Principal\Domain\ValueObject\ConditionKey;
use Source\Wiki\Principal\Domain\ValueObject\ConditionOperator;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class SystemPolicySeeder extends Seeder
{
    public function __construct(
        private readonly PolicyFactoryInterface $policyFactory,
        private readonly PolicyRepositoryInterface $policyRepository,
    ) {
    }

    public function run(): void
    {
        $this->createFullAccessPolicy();
        $this->createBasicEditingPolicy();
        $this->createAgencyManagementPolicy();
        $this->createTalentManagementPolicy();
        $this->createDenyAgencyApprovalPolicy();
        $this->createDenyRollbackPolicy();
    }

    private function createFullAccessPolicy(): void
    {
        $policy = $this->policyFactory->create(
            name: 'FULL_ACCESS',
            statements: [
                new Statement(
                    effect: Effect::ALLOW,
                    actions: Action::cases(),
                    resourceTypes: ResourceType::cases(),
                    condition: null,
                ),
            ],
            isSystemPolicy: true,
        );

        $this->policyRepository->save($policy);
    }

    private function createBasicEditingPolicy(): void
    {
        $policy = $this->policyFactory->create(
            name: 'BASIC_EDITING',
            statements: [
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::CREATE, Action::EDIT, Action::SUBMIT],
                    resourceTypes: ResourceType::cases(),
                    condition: null,
                ),
            ],
            isSystemPolicy: true,
        );

        $this->policyRepository->save($policy);
    }

    private function createAgencyManagementPolicy(): void
    {
        $policy = $this->policyFactory->create(
            name: 'AGENCY_MANAGEMENT',
            statements: [
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE, Action::AUTOMATIC_CREATE],
                    resourceTypes: ResourceType::cases(),
                    condition: new Condition([
                        new ConditionClause(
                            ConditionKey::RESOURCE_AGENCY_ID,
                            ConditionOperator::EQUALS,
                            ConditionValue::PRINCIPAL_AGENCY_ID,
                        ),
                    ]),
                ),
            ],
            isSystemPolicy: true,
        );

        $this->policyRepository->save($policy);
    }

    private function createTalentManagementPolicy(): void
    {
        $policy = $this->policyFactory->create(
            name: 'TALENT_MANAGEMENT',
            statements: [
                // Group 承認系（自分の Group）
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE, Action::AUTOMATIC_CREATE],
                    resourceTypes: [ResourceType::GROUP],
                    condition: new Condition([
                        new ConditionClause(
                            ConditionKey::RESOURCE_GROUP_ID,
                            ConditionOperator::IN,
                            ConditionValue::PRINCIPAL_WIKI_GROUP_IDS,
                        ),
                    ]),
                ),
                // Talent 承認系（自分のタレント）
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE, Action::AUTOMATIC_CREATE],
                    resourceTypes: [ResourceType::TALENT],
                    condition: new Condition([
                        new ConditionClause(
                            ConditionKey::RESOURCE_TALENT_ID,
                            ConditionOperator::IN,
                            ConditionValue::PRINCIPAL_TALENT_IDS,
                        ),
                    ]),
                ),
                // Song 承認系（自分の Group の Song）
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE, Action::AUTOMATIC_CREATE],
                    resourceTypes: [ResourceType::SONG],
                    condition: new Condition([
                        new ConditionClause(
                            ConditionKey::RESOURCE_GROUP_ID,
                            ConditionOperator::IN,
                            ConditionValue::PRINCIPAL_WIKI_GROUP_IDS,
                        ),
                    ]),
                ),
                // Song 承認系（自分の Talent の Song）
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE, Action::AUTOMATIC_CREATE],
                    resourceTypes: [ResourceType::SONG],
                    condition: new Condition([
                        new ConditionClause(
                            ConditionKey::RESOURCE_TALENT_ID,
                            ConditionOperator::IN,
                            ConditionValue::PRINCIPAL_TALENT_IDS,
                        ),
                    ]),
                ),
            ],
            isSystemPolicy: true,
        );

        $this->policyRepository->save($policy);
    }

    private function createDenyAgencyApprovalPolicy(): void
    {
        $policy = $this->policyFactory->create(
            name: 'DENY_AGENCY_APPROVAL',
            statements: [
                new Statement(
                    effect: Effect::DENY,
                    actions: [Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH],
                    resourceTypes: [ResourceType::AGENCY],
                    condition: null,
                ),
            ],
            isSystemPolicy: true,
        );

        $this->policyRepository->save($policy);
    }

    private function createDenyRollbackPolicy(): void
    {
        $policy = $this->policyFactory->create(
            name: 'DENY_ROLLBACK',
            statements: [
                new Statement(
                    effect: Effect::DENY,
                    actions: [Action::ROLLBACK],
                    resourceTypes: ResourceType::cases(),
                    condition: null,
                ),
            ],
            isSystemPolicy: true,
        );

        $this->policyRepository->save($policy);
    }
}
