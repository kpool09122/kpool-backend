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
        $this->createGlobalActionPolicies();
        $this->createOwnWikiActionPolicies();
        $this->createAgencyScopeActionPolicies();
        $this->createTalentScopeActionPolicies();
        $this->createOfficialCertificationRequestPolicies();
        $this->createDenyAgencyActionPolicies();
        $this->createDenyRollbackPolicy();
    }

    private function createGlobalActionPolicies(): void
    {
        foreach (Action::cases() as $action) {
            if ($action === Action::OFFICIAL_CERTIFICATION_REQUEST) {
                $this->createGlobalOfficialCertificationRequestPolicy();

                continue;
            }

            $this->createPolicy(
                name: 'GLOBAL_' . $this->policyActionName($action),
                effect: Effect::ALLOW,
                action: $action,
                resourceTypes: ResourceType::cases(),
            );
        }
    }

    private function createGlobalOfficialCertificationRequestPolicy(): void
    {
        $policy = $this->policyFactory->create(
            name: 'GLOBAL_' . $this->policyActionName(Action::OFFICIAL_CERTIFICATION_REQUEST),
            statements: [
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::OFFICIAL_CERTIFICATION_REQUEST],
                    resourceTypes: [ResourceType::AGENCY],
                    condition: null,
                ),
                new Statement(
                    effect: Effect::ALLOW,
                    actions: [Action::OFFICIAL_CERTIFICATION_REQUEST],
                    resourceTypes: [ResourceType::TALENT],
                    condition: null,
                ),
            ],
            isSystemPolicy: true,
        );

        $this->policyRepository->save($policy);
    }

    private function createOwnWikiActionPolicies(): void
    {
        foreach ([Action::DELETE, Action::WITHDRAW] as $action) {
            $this->createPolicy(
                name: 'OWN_WIKI_' . $this->policyActionName($action),
                effect: Effect::ALLOW,
                action: $action,
                resourceTypes: ResourceType::cases(),
                condition: $this->editorCondition(),
            );
        }
    }

    private function createAgencyScopeActionPolicies(): void
    {
        foreach ([Action::READ, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE, Action::AUTOMATIC_CREATE, Action::SAVE_VIDEO_LINKS] as $action) {
            $this->createPolicy(
                name: 'AGENCY_SCOPE_' . $this->policyActionName($action),
                effect: Effect::ALLOW,
                action: $action,
                resourceTypes: [ResourceType::AGENCY, ResourceType::GROUP, ResourceType::SONG],
                condition: $this->agencyCondition(),
            );
        }

        foreach ([Action::APPROVE, Action::REJECT, Action::DELETE] as $action) {
            $this->createPolicy(
                name: 'AGENCY_SCOPE_IMAGE_' . $this->policyActionName($action),
                effect: Effect::ALLOW,
                action: $action,
                resourceTypes: [ResourceType::IMAGE],
                condition: $this->agencyCondition(),
            );
        }
    }

    private function createTalentScopeActionPolicies(): void
    {
        foreach ([Action::READ, Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH, Action::MERGE, Action::AUTOMATIC_CREATE, Action::SAVE_VIDEO_LINKS] as $action) {
            $this->createPolicy(
                name: 'TALENT_SCOPE_' . $this->policyActionName($action),
                effect: Effect::ALLOW,
                action: $action,
                resourceTypes: [ResourceType::TALENT],
                condition: $this->talentCondition(),
            );
        }

        foreach ([Action::APPROVE, Action::REJECT, Action::DELETE] as $action) {
            $this->createPolicy(
                name: 'TALENT_SCOPE_IMAGE_' . $this->policyActionName($action),
                effect: Effect::ALLOW,
                action: $action,
                resourceTypes: [ResourceType::IMAGE],
                condition: $this->talentCondition(),
            );
        }
    }

    private function createOfficialCertificationRequestPolicies(): void
    {
        $this->createPolicy(
            name: 'AGENCY_SCOPE_OFFICIAL_CERTIFICATION_REQUEST',
            effect: Effect::ALLOW,
            action: Action::OFFICIAL_CERTIFICATION_REQUEST,
            resourceTypes: [ResourceType::AGENCY],
            condition: $this->agencyCondition(),
        );

        $this->createPolicy(
            name: 'TALENT_SCOPE_OFFICIAL_CERTIFICATION_REQUEST',
            effect: Effect::ALLOW,
            action: Action::OFFICIAL_CERTIFICATION_REQUEST,
            resourceTypes: [ResourceType::TALENT],
            condition: $this->talentCondition(),
        );
    }

    private function createDenyAgencyActionPolicies(): void
    {
        foreach ([Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH] as $action) {
            $this->createPolicy(
                name: 'DENY_AGENCY_' . $this->policyActionName($action),
                effect: Effect::DENY,
                action: $action,
                resourceTypes: [ResourceType::AGENCY],
            );
        }
    }

    private function createDenyRollbackPolicy(): void
    {
        $this->createPolicy(
            name: 'DENY_ROLLBACK',
            effect: Effect::DENY,
            action: Action::ROLLBACK,
            resourceTypes: ResourceType::cases(),
        );
    }

    /**
     * @param ResourceType[] $resourceTypes
     */
    private function createPolicy(string $name, Effect $effect, Action $action, array $resourceTypes, ?Condition $condition = null): void
    {
        $policy = $this->policyFactory->create(
            name: $name,
            statements: [
                new Statement(
                    effect: $effect,
                    actions: [$action],
                    resourceTypes: $resourceTypes,
                    condition: $condition,
                ),
            ],
            isSystemPolicy: true,
        );

        $this->policyRepository->save($policy);
    }

    private function editorCondition(): Condition
    {
        return new Condition([
            new ConditionClause(
                ConditionKey::RESOURCE_EDITOR_ID,
                ConditionOperator::EQUALS,
                ConditionValue::PRINCIPAL_ID,
            ),
        ]);
    }

    private function agencyCondition(): Condition
    {
        return new Condition([
            new ConditionClause(
                ConditionKey::RESOURCE_AGENCY_ID,
                ConditionOperator::EQUALS,
                ConditionValue::PRINCIPAL_AGENCY_WIKI_IDENTIFIERS,
            ),
        ]);
    }

    private function talentCondition(): Condition
    {
        return new Condition([
            new ConditionClause(
                ConditionKey::RESOURCE_TALENT_ID,
                ConditionOperator::IN,
                ConditionValue::PRINCIPAL_TALENT_WIKI_IDENTIFIERS,
            ),
        ]);
    }

    private function policyActionName(Action $action): string
    {
        return str_replace('-', '_', strtoupper($action->value));
    }
}
