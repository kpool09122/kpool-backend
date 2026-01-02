<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\ScopeCondition;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class PolicyEvaluator implements PolicyEvaluatorInterface
{
    public function evaluate(
        Principal $principal,
        Action $action,
        ResourceIdentifier $resource,
    ): bool {
        $role = $principal->role();
        $policies = $role->policies();

        // 全Policyの全Statementを収集
        $statements = [];
        foreach ($policies as $policy) {
            array_push($statements, ...$policy->statements());
        }

        // 該当するStatementを抽出（action + resourceType一致）
        $applicableStatements = $this->filterApplicable($statements, $action, $resource->type());

        // Deny優先チェック
        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::DENY) {
                if ($this->matchesScope($statement, $resource, $principal)) {
                    return false; // 明示的Deny
                }
            }
        }

        // Allowチェック
        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::ALLOW) {
                if ($this->matchesScope($statement, $resource, $principal)) {
                    return true; // 明示的Allow
                }
            }
        }

        return false; // 暗黙的Deny
    }

    /**
     * @param Statement[] $statements
     * @return Statement[]
     */
    private function filterApplicable(array $statements, Action $action, ResourceType $resourceType): array
    {
        return array_filter($statements, static function (Statement $statement) use ($action, $resourceType): bool {
            $actionMatches = in_array($action, $statement->actions(), true);
            $resourceMatches = in_array($resourceType, $statement->resourceTypes(), true);

            return $actionMatches && $resourceMatches;
        });
    }

    private function matchesScope(Statement $statement, ResourceIdentifier $resource, Principal $principal): bool
    {
        return match ($statement->scopeCondition()) {
            ScopeCondition::NONE => true,
            ScopeCondition::OWN_AGENCY => $this->matchesOwnAgency($resource, $principal),
            ScopeCondition::OWN_GROUPS => $this->matchesOwnGroups($resource, $principal),
            ScopeCondition::OWN_TALENTS => $this->matchesOwnTalents($resource, $principal),
            ScopeCondition::OWN_GROUPS_OR_TALENTS => $this->matchesOwnGroupsOrTalents($resource, $principal),
        };
    }

    private function matchesOwnAgency(ResourceIdentifier $resource, Principal $principal): bool
    {
        $principalAgencyId = $principal->agencyId();
        if ($principalAgencyId === null) {
            return false;
        }

        $resourceAgencyId = $resource->agencyId();
        if ($resourceAgencyId === null) {
            return false;
        }

        return $resourceAgencyId === $principalAgencyId;
    }

    private function matchesOwnGroups(ResourceIdentifier $resource, Principal $principal): bool
    {
        $principalGroupIds = $principal->groupIds();
        $resourceGroupIds = $resource->groupIds();

        if (empty($principalGroupIds) || empty($resourceGroupIds)) {
            return false;
        }

        return count(array_intersect($resourceGroupIds, $principalGroupIds)) > 0;
    }

    private function matchesOwnTalents(ResourceIdentifier $resource, Principal $principal): bool
    {
        $principalTalentIds = $principal->talentIds();
        $resourceTalentIds = $resource->talentIds();

        if (empty($principalTalentIds) || empty($resourceTalentIds)) {
            return false;
        }

        return count(array_intersect($resourceTalentIds, $principalTalentIds)) > 0;
    }

    private function matchesOwnGroupsOrTalents(ResourceIdentifier $resource, Principal $principal): bool
    {
        return $this->matchesOwnGroups($resource, $principal)
            || $this->matchesOwnTalents($resource, $principal);
    }
}
